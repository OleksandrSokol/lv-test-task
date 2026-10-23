<?php

namespace App\Modules\Marketplace\Events;

use App\Modules\Core\Dtos\ActivityData;
use App\Modules\Core\Enums\ActivitySourceEnum;
use App\Modules\Core\Events\DomainEvent;
use App\Modules\Marketplace\Enums\WebhookStatusEnum;
use App\Modules\Marketplace\Models\WebhookEvent;

class WebhookProcessed extends DomainEvent
{
    public function __construct(
        public readonly WebhookEvent $event,
        public readonly WebhookStatusEnum $status,
    ) {}

    public function activity(): ActivityData
    {
        return new ActivityData(
            event: 'webhook.' . $this->status->value,
            source: ActivitySourceEnum::WEBHOOK,
            subject: $this->event,
            data: [
                'event_id' => $this->event->event_id,
                'type'     => $this->event->type,
                // Null for an event of a type we ignore: it does not touch an order.
                'order_id'          => $this->event->order?->id,
                'order_external_id' => $this->event->order_external_id,
            ],
        );
    }
}
