<?php

namespace App\Modules\Marketplace\Enums;

use App\Modules\Core\Traits\Enums\HasEnumHelpers;

/**
 * What happened to a received event. `DUPLICATE` is only ever a response —
 * a duplicate is not stored, the original row keeps its status.
 */
enum WebhookStatusEnum: string
{
    use HasEnumHelpers;

    case RECEIVED = 'received';
    case APPLIED = 'applied';
    case STALE = 'stale';
    case IGNORED = 'ignored';
    case DUPLICATE = 'duplicate';

    public function label(): string
    {
        return __("enums.webhook_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::RECEIVED  => 'gray',
            self::APPLIED   => 'success',
            self::STALE     => 'warning',
            self::IGNORED   => 'gray',
            self::DUPLICATE => 'info',
        };
    }
}
