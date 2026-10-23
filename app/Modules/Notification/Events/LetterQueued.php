<?php

namespace App\Modules\Notification\Events;

use App\Modules\Core\Enums\ActivityEventEnum;

class LetterQueued extends LetterEvent
{
    protected function name(): ActivityEventEnum
    {
        return ActivityEventEnum::LETTER_QUEUED;
    }
}
