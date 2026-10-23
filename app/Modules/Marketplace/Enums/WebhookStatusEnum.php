<?php

namespace App\Modules\Marketplace\Enums;

/**
 * What happened to a received event. `DUPLICATE` is only ever a response —
 * a duplicate is not stored, the original row keeps its status.
 */
enum WebhookStatusEnum: string
{
    case RECEIVED = 'received';
    case APPLIED = 'applied';
    case STALE = 'stale';
    case IGNORED = 'ignored';
    case DUPLICATE = 'duplicate';
}
