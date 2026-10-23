<?php

namespace App\Modules\Order\Enums;

enum OrderStatusEnum: string
{
    case CREATED = 'created';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
}
