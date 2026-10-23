<?php

namespace App\Modules\Order\Events;

use App\Modules\Order\Models\Order;
use Carbon\CarbonImmutable;

class OrderSnapshotStale extends OrderEvent
{
    public function __construct(
        Order $order,
        public readonly CarbonImmutable $snapshotUpdatedAt,
    ) {
        parent::__construct($order);
    }

    protected function name(): string
    {
        return 'order.snapshot_stale';
    }

    protected function details(): array
    {
        return [
            'snapshot_updated_at' => $this->snapshotUpdatedAt->toIso8601String(),
            'stored_updated_at'   => $this->order->external_updated_at->toIso8601String(),
        ];
    }
}
