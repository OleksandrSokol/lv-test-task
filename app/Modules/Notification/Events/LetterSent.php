<?php

namespace App\Modules\Notification\Events;

class LetterSent extends LetterEvent
{
    protected function name(): string
    {
        return 'letter.sent';
    }
}
