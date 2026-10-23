<?php

namespace App\Modules\Order\Listeners;

use App\Modules\Notification\Dtos\SendLetterData;
use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Services\LetterService;
use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

/**
 * Tells the customer about the order's new status, unless the letter would contradict
 * the order's current state by the time the queue gets to it.
 */
class SendOrderStatusLetter implements ShouldQueue
{
    public function __construct(
        private readonly LetterService $letters,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        DB::transaction(function () use ($event) {
            // Waits for a snapshot being applied to this order, so the status read here is final.
            $order = Order::query()->with('items')->lockForUpdate()->find($event->order->id);

            if ($order === null) {
                return;
            }

            $allowed = $this->allowedLetters($order->status);

            $this->letters->skipPending(
                $order->id,
                keep: $allowed,
                reason: __('letters.skipped_reason', ['status' => $order->status->value]),
            );

            $letter = $this->letterFor($event->to);

            if ($letter !== null && in_array($letter, $allowed, true)) {
                $this->letters->send(new SendLetterData(
                    key: $letter,
                    to: $order->customer_email,
                    replace: $this->replace($order),
                    orderId: $order->id,
                ));
            }
        });
    }

    /**
     * Letters that do not contradict an order in this status: a shipped order is a paid one,
     * a cancelled order is neither paid for good nor shipped.
     *
     * @return list<LetterEnum>
     */
    private function allowedLetters(OrderStatusEnum $status): array
    {
        return match ($status) {
            OrderStatusEnum::CREATED => [],
            OrderStatusEnum::PAID    => [LetterEnum::ORDER_PAID],
            OrderStatusEnum::SHIPPED,
            OrderStatusEnum::DELIVERED => [LetterEnum::ORDER_PAID, LetterEnum::ORDER_SHIPPED],
            OrderStatusEnum::CANCELLED => [LetterEnum::ORDER_CANCELLED],
        };
    }

    private function letterFor(OrderStatusEnum $status): ?LetterEnum
    {
        return match ($status) {
            OrderStatusEnum::PAID      => LetterEnum::ORDER_PAID,
            OrderStatusEnum::SHIPPED   => LetterEnum::ORDER_SHIPPED,
            OrderStatusEnum::CANCELLED => LetterEnum::ORDER_CANCELLED,
            default                    => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private function replace(Order $order): array
    {
        $currency = $order->currency->value;

        return [
            'number'        => $order->number,
            'customer_name' => $order->customer_name,
            'currency'      => $currency,
            'items'         => $order->items->map(fn (OrderItem $item) => __('letters.item_line', [
                'name'     => $item->name,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
                'currency' => $currency,
            ]))->implode("\n"),
            'delivery_price'  => $order->delivery_price,
            'discount'        => $order->discount,
            'total'           => $order->total,
            'carrier'         => (string) $order->shipment_carrier,
            'tracking_number' => (string) $order->shipment_tracking_number,
            'tracking_url'    => (string) $order->shipment_tracking_url,
            'cancel_reason'   => $order->cancel_reason ?? __('letters.no_reason'),
            'refund_note'     => $order->paid_at !== null ? __('letters.refund_note') : '',
        ];
    }
}
