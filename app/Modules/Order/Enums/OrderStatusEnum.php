<?php

namespace App\Modules\Order\Enums;

use App\Modules\Core\Traits\Enums\HasEnumHelpers;

enum OrderStatusEnum: string
{
    use HasEnumHelpers;

    case CREATED = 'created';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __("enums.order_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::CREATED   => 'gray',
            self::PAID      => 'info',
            self::SHIPPED   => 'warning',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
