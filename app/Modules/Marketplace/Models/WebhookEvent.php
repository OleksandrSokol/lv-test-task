<?php

namespace App\Modules\Marketplace\Models;

use App\Modules\Core\Traits\Models\StoresDatesWithOffset;
use App\Modules\Marketplace\Enums\WebhookStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Inbox of received marketplace events. The unique `event_id` makes redeliveries no-ops.
 */
#[Fillable(['event_id', 'type', 'order_external_id', 'payload', 'occurred_at', 'status'])]
class WebhookEvent extends Model
{
    use StoresDatesWithOffset;

    protected function casts(): array
    {
        return [
            'payload'     => 'array',
            'status'      => WebhookStatusEnum::class,
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
