<?php

namespace App\Modules\Notification\Enums;

enum LogLetterStatusEnum: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case FAILED = 'failed';

    // Was queued, but withdrawn before sending: the order changed and the letter
    // would now contradict it (LetterService::skipPending).
    case SKIPPED = 'skipped';
}
