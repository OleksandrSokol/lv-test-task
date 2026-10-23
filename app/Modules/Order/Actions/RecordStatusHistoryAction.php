<?php

namespace App\Modules\Order\Actions;

use App\Modules\Order\Dtos\OrderSnapshotData;
use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderStatusHistory;
use Carbon\CarbonImmutable;

/**
 * Completes the order's status history with what a snapshot tells about the past.
 *
 * Any snapshot counts, a stale one too: it may be the only witness of a status the order
 * has already left. Must run under the order row lock (ApplyOrderSnapshotAction).
 */
class RecordStatusHistoryAction
{
    public function handle(Order $order, OrderSnapshotData $snapshot): void
    {
        // Statuses with their own timestamp in the snapshot: the moment is exact.
        $exact = array_filter([
            [OrderStatusEnum::CREATED, $snapshot->createdAt],
            [OrderStatusEnum::PAID, $snapshot->paidAt],
            [OrderStatusEnum::CANCELLED, $snapshot->cancelledAt],
        ], fn (array $fact) => $fact[1] !== null);

        foreach ($exact as [$status, $at]) {
            $order->history()->firstOrCreate(['status' => $status, 'changed_at' => $at]);
        }

        if (!$this->hasOwnTimestamp($snapshot)) {
            $this->recordObserved($order, $snapshot->status, $snapshot->updatedAt);
        }
    }

    private function hasOwnTimestamp(OrderSnapshotData $snapshot): bool
    {
        return match ($snapshot->status) {
            OrderStatusEnum::CREATED   => true,
            OrderStatusEnum::PAID      => $snapshot->paidAt !== null,
            OrderStatusEnum::CANCELLED => $snapshot->cancelledAt !== null,
            default                    => false,
        };
    }

    /**
     * The order was in `$status` at `$at`; the transition itself happened at or before it.
     */
    private function recordObserved(Order $order, OrderStatusEnum $status, CarbonImmutable $at): void
    {
        $history = $order->history()->get();

        // Already in this status by then: an earlier moment is recorded.
        $before = $history->last(fn (OrderStatusHistory $entry) => $entry->changed_at->lte($at));

        if ($before?->status === $status) {
            return;
        }

        // The same stint was recorded later from a newer snapshot that arrived first: move it back.
        $after = $history->first(fn (OrderStatusHistory $entry) => $entry->changed_at->gt($at));

        if ($after?->status === $status) {
            $after->update(['changed_at' => $at]);

            return;
        }

        $order->history()->create(['status' => $status, 'changed_at' => $at]);
    }
}
