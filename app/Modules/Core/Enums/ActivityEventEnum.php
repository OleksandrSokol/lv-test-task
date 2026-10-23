<?php

namespace App\Modules\Core\Enums;

use App\Modules\Core\Traits\Enums\HasEnumHelpers;

/**
 * What an activity log row records. One case per domain event the modules dispatch.
 */
enum ActivityEventEnum: string
{
    use HasEnumHelpers;

    case WEBHOOK_APPLIED = 'webhook.applied';
    case WEBHOOK_STALE = 'webhook.stale';
    case WEBHOOK_IGNORED = 'webhook.ignored';
    case WEBHOOK_DUPLICATE = 'webhook.duplicate';
    case ORDER_CREATED = 'order.created';
    case ORDER_UPDATED = 'order.updated';
    case ORDER_STATUS_CHANGED = 'order.status_changed';
    case ORDER_SNAPSHOT_STALE = 'order.snapshot_stale';
    case LETTER_QUEUED = 'letter.queued';
    case LETTER_SENT = 'letter.sent';
    case LETTER_FAILED = 'letter.failed';
    case LETTER_SKIPPED = 'letter.skipped';

    public function label(): string
    {
        // Values contain dots, so they cannot be a translation key path.
        return __('enums.activity_event')[$this->value] ?? $this->value;
    }
}
