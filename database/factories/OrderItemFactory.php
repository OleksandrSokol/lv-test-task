<?php

namespace Database\Factories;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'position' => 0,
            'sku'      => fake()->unique()->bothify('SKU-????-##'),
            'name'     => fake()->words(3, true),
            'quantity' => fake()->numberBetween(1, 3),
            'price'    => fake()->numberBetween(100, 5000) . '.00',
        ];
    }
}
