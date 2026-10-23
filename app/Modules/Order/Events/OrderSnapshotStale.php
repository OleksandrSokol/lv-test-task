<?php

namespace App\Modules\Order\Events;

use App\Modules\Core\Enums\ActivityEventEnum;
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

    protected function name(): ActivityEventEnum
    {
        return ActivityEventEnum::ORDER_SNAPSHOT_STALE;
    }

    protected function details(): array
    {
        return [
            'snapshot_updated_at' => $this->snapshotUpdatedAt->toIso8601String(),
            'stored_updated_at'   => $this->order->external_updated_at->toIso8601String(),
        ];
    }
}
