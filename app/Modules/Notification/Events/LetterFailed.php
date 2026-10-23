<?php

namespace App\Modules\Notification\Events;

use App\Modules\Core\Enums\ActivityEventEnum;

class LetterFailed extends LetterEvent
{
    protected function name(): ActivityEventEnum
    {
        return ActivityEventEnum::LETTER_FAILED;
    }

    protected function details(): array
    {
        return ['error' => $this->letter->error_message];
    }
}
