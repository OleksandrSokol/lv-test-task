<?php

namespace App\Modules\Notification\Events;

class LetterFailed extends LetterEvent
{
    protected function name(): string
    {
        return 'letter.failed';
    }

    protected function details(): array
    {
        return ['error' => $this->letter->error_message];
    }
}
