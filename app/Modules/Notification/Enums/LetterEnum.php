<?php

namespace App\Modules\Notification\Enums;

/**
 * Letter templates, one row in `letter_templates` each.
 */
enum LetterEnum: string
{
    case ORDER_PAID = 'order_paid';
    case ORDER_SHIPPED = 'order_shipped';
    case ORDER_CANCELLED = 'order_cancelled';
}
