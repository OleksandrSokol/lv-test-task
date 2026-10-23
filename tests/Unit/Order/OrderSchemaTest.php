<?php

namespace Tests\Unit\Order;

use App\Modules\Order\Enums\CurrencyEnum;
use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\OrderStatusHistory;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_subtotal_is_price_times_quantity(): void
    {
        $this->assertSame('3870.00', OrderItem::factory()->make(['price' => '1290.00', 'quantity' => 3])->subtotal);
        $this->assertSame('2671.50', OrderItem::factory()->make(['price' => '890.50', 'quantity' => 3])->subtotal);
        $this->assertSame('0.30', OrderItem::factory()->make(['price' => '0.10', 'quantity' => 3])->subtotal);
    }

    public function test_amounts_are_read_as_two_decimal_strings(): void
    {
        $order = Order::factory()->create(['total' => '4909.5', 'discount' => '0']);

        $order->refresh();

        $this->assertSame('4909.50', $order->total);
        $this->assertSame('0.00', $order->discount);
    }

    public function test_marketplace_moment_is_kept_regardless_of_offset(): void
    {
        $order = Order::factory()->create([
            'external_created_at' => CarbonImmutable::parse('2026-10-21T13:02:11+03:00'),
        ]);

        $this->assertSame('2026-10-21T10:02:11+00:00', $order->refresh()->external_created_at->toIso8601String());
    }

    public function test_status_and_currency_are_cast_to_enums(): void
    {
        $order = Order::factory()->paid()->create(['currency' => 'USD'])->refresh();

        $this->assertSame(OrderStatusEnum::PAID, $order->status);
        $this->assertSame(CurrencyEnum::USD, $order->currency);
    }

    public function test_relations_are_ordered(): void
    {
        $order = Order::factory()->create();
        OrderItem::factory()->for($order)->create(['position' => 1, 'sku' => 'B']);
        OrderItem::factory()->for($order)->create(['position' => 0, 'sku' => 'A']);
        OrderStatusHistory::factory()->for($order)->create(['status' => OrderStatusEnum::PAID, 'changed_at' => '2026-10-21 10:05:47']);
        OrderStatusHistory::factory()->for($order)->create(['status' => OrderStatusEnum::CREATED, 'changed_at' => '2026-10-21 10:02:11']);

        $this->assertSame(['A', 'B'], $order->items->pluck('sku')->all());
        $this->assertSame([OrderStatusEnum::CREATED, OrderStatusEnum::PAID], $order->history->pluck('status')->all());
    }

    public function test_same_status_at_same_moment_is_stored_once(): void
    {
        $entry = OrderStatusHistory::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);

        OrderStatusHistory::factory()->create([
            'order_id'   => $entry->order_id,
            'status'     => $entry->status,
            'changed_at' => $entry->changed_at,
        ]);
    }
}
