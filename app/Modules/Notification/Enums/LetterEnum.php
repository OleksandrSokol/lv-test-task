<?php

namespace App\Modules\Notification\Enums;

use App\Modules\Core\Traits\Enums\HasEnumHelpers;

/**
 * Letter templates, one row in `letter_templates` each.
 */
enum LetterEnum: string
{
    use HasEnumHelpers;

    case ORDER_PAID = 'order_paid';
    case ORDER_SHIPPED = 'order_shipped';
    case ORDER_CANCELLED = 'order_cancelled';

    public function label(): string
    {
        return __("enums.letter.{$this->value}");
    }
}
