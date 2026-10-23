<?php

namespace Database\Factories;

use App\Modules\Order\Enums\CurrencyEnum;
use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $placedAt = now()->subHours(fake()->numberBetween(1, 72))->startOfSecond()->toImmutable();

        return [
            'external_id'         => 'ord_' . fake()->unique()->bothify('########'),
            'number'              => 'MH-' . fake()->unique()->numerify('#######'),
            'status'              => OrderStatusEnum::CREATED,
            'currency'            => CurrencyEnum::RUB,
            'customer_email'      => fake()->unique()->safeEmail(),
            'customer_name'       => fake()->name(),
            'customer_phone'      => fake()->optional()->numerify('+7916#######'),
            'delivery_price'      => '349.00',
            'discount'            => '0.00',
            'total'               => fake()->numberBetween(500, 20000) . '.00',
            'external_created_at' => $placedAt,
            'external_updated_at' => $placedAt,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'              => OrderStatusEnum::PAID,
            'paid_at'             => $attributes['external_created_at']->addMinutes(3),
            'external_updated_at' => $attributes['external_created_at']->addMinutes(3),
        ]);
    }

    public function shipped(): static
    {
        return $this->paid()->state(fn (array $attributes) => [
            'status'                   => OrderStatusEnum::SHIPPED,
            'shipment_carrier'         => 'СДЭК',
            'shipment_tracking_number' => '1098765432',
            'shipment_tracking_url'    => 'https://www.cdek.ru/ru/tracking?order_id=1098765432',
            'external_updated_at'      => $attributes['external_created_at']->addDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'              => OrderStatusEnum::CANCELLED,
            'cancelled_at'        => $attributes['external_created_at']->addHour(),
            'cancel_reason'       => 'Покупатель отказался от заказа',
            'external_updated_at' => $attributes['external_created_at']->addHour(),
        ]);
    }
}
