<?php

namespace App\Modules\Order\Events;

use App\Modules\Core\Dtos\ActivityData;
use App\Modules\Core\Enums\ActivitySourceEnum;
use App\Modules\Core\Events\DomainEvent;
use App\Modules\Order\Models\Order;

/**
 * Something that happened to an order. Orders change only through marketplace snapshots,
 * so the source is always the webhook.
 */
abstract class OrderEvent extends DomainEvent
{
    public function __construct(
        public readonly Order $order,
    ) {}

    abstract protected function name(): string;

    /**
     * @return array<string, mixed>
     */
    protected function details(): array
    {
        return [];
    }

    public function activity(): ActivityData
    {
        return new ActivityData(
            event: $this->name(),
            source: ActivitySourceEnum::WEBHOOK,
            subject: $this->order,
            data: ['order_id' => $this->order->external_id, ...$this->details()],
        );
    }
}
