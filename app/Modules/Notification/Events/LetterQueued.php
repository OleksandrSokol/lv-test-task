<?php

namespace App\Modules\Notification\Events;

class LetterQueued extends LetterEvent
{
    protected function name(): string
    {
        return 'letter.queued';
    }
}
