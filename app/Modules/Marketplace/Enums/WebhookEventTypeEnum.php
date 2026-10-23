<?php

namespace App\Modules\Marketplace\Enums;

use App\Modules\Core\Traits\Enums\HasEnumHelpers;

/**
 * Event types we know how to handle. The marketplace may add new ones at any time.
 */
enum WebhookEventTypeEnum: string
{
    use HasEnumHelpers;

    case ORDER_CREATED = 'order.created';
    case ORDER_UPDATED = 'order.updated';
    case ORDER_PAID = 'order.paid';
    case ORDER_SHIPPED = 'order.shipped';
    case ORDER_DELIVERED = 'order.delivered';
    case ORDER_CANCELLED = 'order.cancelled';

    public function label(): string
    {
        // Values contain dots, so they cannot be a translation key path.
        return __('enums.webhook_event_type')[$this->value] ?? $this->value;
    }
}
