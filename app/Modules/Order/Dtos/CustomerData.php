<?php

namespace App\Modules\Order\Dtos;

use Spatie\LaravelData\Data;

class CustomerData extends Data
{
    public function __construct(
        public string $email,
        public string $name,
        public ?string $phone,
    ) {}
}
