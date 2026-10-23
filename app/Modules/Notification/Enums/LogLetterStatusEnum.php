<?php

namespace App\Modules\Notification\Enums;

use App\Modules\Core\Traits\Enums\HasEnumHelpers;

enum LogLetterStatusEnum: string
{
    use HasEnumHelpers;

    case QUEUED = 'queued';
    case SENT = 'sent';
    case FAILED = 'failed';

    // Was queued, but withdrawn before sending: the order changed and the letter
    // would now contradict it (LetterService::skipPending).
    case SKIPPED = 'skipped';

    public function label(): string
    {
        return __("enums.log_letter_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::QUEUED  => 'gray',
            self::SENT    => 'success',
            self::FAILED  => 'danger',
            self::SKIPPED => 'warning',
        };
    }
}
