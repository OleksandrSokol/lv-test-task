<?php

namespace App\Modules\Core\Enums;

/**
 * Where an action recorded in the activity log came from.
 */
enum ActivitySourceEnum: string
{
    case WEBHOOK = 'webhook';
    case QUEUE = 'queue';
}
