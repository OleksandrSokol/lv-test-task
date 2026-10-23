<?php

namespace App\Modules\Order\Dtos;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapInputName(SnakeCaseMapper::class)]
class ShipmentData extends Data
{
    public function __construct(
        public string $carrier,
        public string $trackingNumber,
        public string $trackingUrl,
    ) {}
}
