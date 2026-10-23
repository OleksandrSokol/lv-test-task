<?php

namespace Tests\Unit\Core\Fixtures;

use App\Modules\Core\Dtos\ActivityData;
use App\Modules\Core\Enums\ActivityEventEnum;
use App\Modules\Core\Enums\ActivitySourceEnum;
use App\Modules\Core\Events\DomainEvent;
use App\Modules\User\Models\User;

class UserRenamed extends DomainEvent
{
    public function __construct(
        public readonly User $user,
        public readonly string $from,
    ) {}

    public function activity(): ActivityData
    {
        return new ActivityData(
            // Any case will do: the test is about the listener, not the event's name.
            event: ActivityEventEnum::ORDER_UPDATED,
            source: ActivitySourceEnum::QUEUE,
            subject: $this->user,
            data: ['old' => $this->from, 'new' => $this->user->name],
        );
    }
}
