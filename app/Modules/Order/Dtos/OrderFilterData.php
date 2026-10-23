<?php

namespace App\Modules\Order\Dtos;

use App\Modules\Order\Enums\OrderStatusEnum;
use Spatie\LaravelData\Data;

/**
 * Criteria of the admin's order list.
 */
class OrderFilterData extends Data
{
    public function __construct(
        public ?OrderStatusEnum $status = null,
        public ?string $email = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
