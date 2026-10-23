<?php

namespace App\Modules\Notification\Events;

use App\Modules\Notification\Models\LogLetter;

class LetterSkipped extends LetterEvent
{
    public function __construct(
        LogLetter $letter,
        public readonly string $reason,
    ) {
        parent::__construct($letter);
    }

    protected function name(): string
    {
        return 'letter.skipped';
    }

    protected function details(): array
    {
        return ['reason' => $this->reason];
    }
}
