<?php

namespace App\Modules\Core\Enums;

use App\Modules\Core\Traits\Enums\HasEnumHelpers;

/**
 * Where an action recorded in the activity log came from.
 */
enum ActivitySourceEnum: string
{
    use HasEnumHelpers;

    case WEBHOOK = 'webhook';
    case QUEUE = 'queue';

    public function label(): string
    {
        return __("enums.activity_source.{$this->value}");
    }
}
