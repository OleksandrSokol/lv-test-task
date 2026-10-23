<?php

namespace App\Modules\Order\Actions;

use App\Modules\Order\Dtos\OrderItemData;
use App\Modules\Order\Dtos\OrderSnapshotData;
use App\Modules\Order\Enums\SnapshotOutcomeEnum;
use App\Modules\Order\Events\OrderCreated;
use App\Modules\Order\Events\OrderSnapshotStale;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Events\OrderUpdated;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * Brings the stored order to the state of a marketplace snapshot.
 *
 * Deliveries come in any order, so freshness is decided by the order's `updated_at`,
 * not by arrival or by status: support may move a status back by hand.
 */
class ApplyOrderSnapshotAction
{
    public function __construct(
        private readonly RecordStatusHistoryAction $history,
    ) {}

    public function handle(OrderSnapshotData $snapshot): SnapshotOutcomeEnum
    {
        return DB::transaction(fn () => $this->apply($snapshot));
    }

    private function apply(OrderSnapshotData $snapshot): SnapshotOutcomeEnum
    {
        // Snapshots of one order are applied one at a time: each waits for this row lock.
        $order = Order::query()->where('external_id', $snapshot->externalId)->lockForUpdate()->first();

        if ($order === null) {
            // A concurrent first snapshot of the same order waits on the unique index until this one commits.
            $order = Order::query()->createOrFirst(['external_id' => $snapshot->externalId], $this->attributes($snapshot));

            if ($order->wasRecentlyCreated) {
                return $this->create($order, $snapshot);
            }

            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
        }

        $this->history->handle($order, $snapshot);

        if ($snapshot->updatedAt->lte($order->external_updated_at)) {
            OrderSnapshotStale::dispatch($order, $snapshot->updatedAt);

            return SnapshotOutcomeEnum::STALE;
        }

        return $this->update($order, $snapshot);
    }

    private function create(Order $order, OrderSnapshotData $snapshot): SnapshotOutcomeEnum
    {
        $this->replaceItems($order, $snapshot);
        $this->history->handle($order, $snapshot);

        OrderCreated::dispatch($order);
        OrderStatusChanged::dispatch($order, null, $order->status);

        return SnapshotOutcomeEnum::APPLIED;
    }

    private function update(Order $order, OrderSnapshotData $snapshot): SnapshotOutcomeEnum
    {
        $from = $order->status;

        $order->fill($this->attributes($snapshot));
        $changes = $this->changes($order);
        $order->save();

        if ($items = $this->replaceItems($order, $snapshot)) {
            $changes['items'] = $items;
        }

        if ($changes !== []) {
            OrderUpdated::dispatch($order, $changes);
        }

        if ($order->status !== $from) {
            OrderStatusChanged::dispatch($order, $from, $order->status);
        }

        return SnapshotOutcomeEnum::APPLIED;
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(OrderSnapshotData $snapshot): array
    {
        return [
            'number'                   => $snapshot->number,
            'status'                   => $snapshot->status,
            'currency'                 => $snapshot->currency,
            'customer_email'           => $snapshot->customer->email,
            'customer_name'            => $snapshot->customer->name,
            'customer_phone'           => $snapshot->customer->phone,
            'delivery_price'           => $snapshot->deliveryPrice,
            'discount'                 => $snapshot->discount,
            'total'                    => $snapshot->total,
            'shipment_carrier'         => $snapshot->shipment?->carrier,
            'shipment_tracking_number' => $snapshot->shipment?->trackingNumber,
            'shipment_tracking_url'    => $snapshot->shipment?->trackingUrl,
            'paid_at'                  => $snapshot->paidAt,
            'cancelled_at'             => $snapshot->cancelledAt,
            'cancel_reason'            => $snapshot->cancelReason,
            'external_created_at'      => $snapshot->createdAt,
            'external_updated_at'      => $snapshot->updatedAt,
        ];
    }

    /**
     * Changed fields of a filled, not yet saved order, for the activity log.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function changes(Order $order): array
    {
        $changes = [];

        foreach (array_keys($order->getDirty()) as $field) {
            // Moves with every snapshot, says nothing on its own.
            if ($field === 'external_updated_at') {
                continue;
            }

            $changes[$field] = [
                'old' => $this->loggable($order->getOriginal($field)),
                'new' => $this->loggable($order->getAttribute($field)),
            ];
        }

        return $changes;
    }

    private function loggable(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum        => $value->value,
            $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
            default                             => $value,
        };
    }

    /**
     * Rewrites the items when the composition changed.
     *
     * @return array{old: list<array<string, mixed>>, new: list<array<string, mixed>>}|null
     */
    private function replaceItems(Order $order, OrderSnapshotData $snapshot): ?array
    {
        $old = $order->items()->get()
            ->map(fn (OrderItem $item) => $item->only(['sku', 'name', 'quantity', 'price']))
            ->all();

        $new = array_map(fn (OrderItemData $item) => [
            'sku'      => $item->sku,
            'name'     => $item->name,
            'quantity' => $item->quantity,
            'price'    => $item->price,
        ], $snapshot->items);

        if ($old === $new) {
            return null;
        }

        $order->items()->delete();

        foreach ($new as $position => $item) {
            $order->items()->create(['position' => $position, ...$item]);
        }

        return ['old' => $old, 'new' => $new];
    }
}
