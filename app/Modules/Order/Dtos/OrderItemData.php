<?php

namespace App\Modules\Order\Dtos;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;

class OrderItemData extends Data
{
    public function __construct(
        public string $sku,
        public string $name,
        #[IntegerType, Min(1)]
        public int $quantity,
        #[Regex(OrderSnapshotData::AMOUNT_PATTERN)]
        public string $price,
    ) {}
}
