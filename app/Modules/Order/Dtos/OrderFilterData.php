<?php

namespace App\Modules\Order\Dtos;

use App\Modules\Order\Enums\OrderStatusEnum;
use Spatie\LaravelData\Data;

/**
 * Criteria of the admin's order list; defaults and bounds live in ListOrdersRequest.
 */
class OrderFilterData extends Data
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?OrderStatusEnum $status = null,
        public ?string $email = null,
    ) {}
}
