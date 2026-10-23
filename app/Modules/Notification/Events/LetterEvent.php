<?php

namespace App\Modules\Notification\Events;

use App\Modules\Core\Dtos\ActivityData;
use App\Modules\Core\Enums\ActivityEventEnum;
use App\Modules\Core\Enums\ActivitySourceEnum;
use App\Modules\Core\Events\DomainEvent;
use App\Modules\Notification\Models\LogLetter;

/**
 * A step in a letter's life. Letters are queued and sent by queue workers.
 */
abstract class LetterEvent extends DomainEvent
{
    public function __construct(
        public readonly LogLetter $letter,
    ) {}

    abstract protected function name(): ActivityEventEnum;

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
            source: ActivitySourceEnum::QUEUE,
            subject: $this->letter,
            data: [
                'template'          => $this->letter->template_key->value,
                'order_id'          => $this->letter->order_id,
                'order_external_id' => $this->letter->order->external_id,
                ...$this->details(),
            ],
        );
    }
}
