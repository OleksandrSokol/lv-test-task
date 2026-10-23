<?php

namespace App\Modules\Marketplace\Enums;

/**
 * Event types we know how to handle. The marketplace may add new ones at any time.
 */
enum WebhookEventTypeEnum: string
{
    case ORDER_CREATED = 'order.created';
    case ORDER_UPDATED = 'order.updated';
    case ORDER_PAID = 'order.paid';
    case ORDER_SHIPPED = 'order.shipped';
    case ORDER_DELIVERED = 'order.delivered';
    case ORDER_CANCELLED = 'order.cancelled';
}
