<?php

namespace Database\Factories;

use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    public function definition(): array
    {
        return [
            'order_id'   => Order::factory(),
            'status'     => OrderStatusEnum::CREATED,
            'changed_at' => now()->startOfSecond(),
        ];
    }
}
