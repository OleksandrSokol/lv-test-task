<?php

namespace App\Modules\Order\Enums;

/**
 * ISO 4217 currencies the marketplace currently sends.
 */
enum CurrencyEnum: string
{
    case RUB = 'RUB';
    case USD = 'USD';
    case EUR = 'EUR';
}
