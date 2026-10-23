<?php

namespace Tests\Feature\Order;

use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SendsMarketplaceWebhooks;
use Tests\TestCase;

class OrdersApiTest extends TestCase
{
    use RefreshDatabase, SendsMarketplaceWebhooks;

    public function test_list_matches_paginated_collection_contract(): void
    {
        Order::factory()->count(2)->create();

        $this->getJson('/api/orders')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id', 'number', 'status', 'customer' => ['email', 'name'],
                    'total', 'currency', 'created_at', 'updated_at',
                ]],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => ['current_page', 'from', 'last_page', 'path', 'per_page', 'to', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_list_sorts_by_placement_time_not_by_receipt(): void
    {
        $placed = CarbonImmutable::parse('2026-10-21T10:00:00+00:00');

        // Received in this order, placed in another.
        Order::factory()->create(['external_id' => 'ord_middle', 'external_created_at' => $placed]);
        Order::factory()->create(['external_id' => 'ord_oldest', 'external_created_at' => $placed->subHour()]);
        Order::factory()->create(['external_id' => 'ord_newest', 'external_created_at' => $placed->addHour()]);

        $ids = $this->getJson('/api/orders')->assertOk()->json('data.*.id');

        $this->assertSame(['ord_newest', 'ord_middle', 'ord_oldest'], $ids);
    }

    public function test_list_filters_by_status(): void
    {
        Order::factory()->create(['external_id' => 'ord_created']);
        Order::factory()->paid()->create(['external_id' => 'ord_paid']);

        $this->getJson('/api/orders?status=paid')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'ord_paid');
    }

    public function test_list_filters_by_email_ignoring_case_but_not_partially(): void
    {
        Order::factory()->create(['external_id' => 'ord_anna', 'customer_email' => 'anna.smirnova@example.com']);
        Order::factory()->create(['external_id' => 'ord_other', 'customer_email' => 'ivan@example.com']);

        $this->getJson('/api/orders?email=Anna.Smirnova@Example.COM')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', 'ord_anna');

        $this->getJson('/api/orders?email=anna')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_list_rejects_out_of_contract_query_with_readable_messages(): void
    {
        $perPage = 'Количество заказов на странице должно быть целым числом от 1 до 100.';

        $this->get('/api/orders?per_page=101')->assertUnprocessable()->assertJsonValidationErrors(['per_page' => $perPage]);
        $this->get('/api/orders?per_page=0')->assertUnprocessable()->assertJsonValidationErrors(['per_page' => $perPage]);
        $this->get('/api/orders?per_page=abc')->assertUnprocessable()->assertJsonValidationErrors(['per_page' => $perPage]);
        $this->get('/api/orders?page=0')->assertUnprocessable()
            ->assertJsonValidationErrors(['page' => 'Номер страницы должен быть целым числом не меньше 1.']);
        $this->get('/api/orders?status=unknown')->assertUnprocessable()->assertJsonValidationErrors([
            'status' => 'Неизвестный статус заказа. Допустимые значения: created, paid, shipped, delivered, cancelled.',
        ]);
        $this->get('/api/orders?email[]=a')->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Email покупателя должен быть строкой не длиннее 255 символов.']);
    }

    public function test_list_second_page_has_correct_meta_and_links_keep_filters(): void
    {
        Order::factory()->count(3)->paid()->create();

        $this->getJson('/api/orders?status=paid&per_page=1&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.from', 2)
            ->assertJsonPath('meta.to', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('links.next', url('/api/orders') . '?status=paid&per_page=1&page=3');
    }

    public function test_card_matches_contract_with_string_amounts(): void
    {
        Mail::fake();
        $this->seedLetterTemplates();
        $this->sendWebhook($this->exampleEvent('order-shipped'))->assertOk();

        $this->getJson('/api/orders/ord_7Q2KX9M4')
            ->assertOk()
            ->assertJsonPath('data.id', 'ord_7Q2KX9M4')
            ->assertJsonPath('data.number', 'MH-1048213')
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.customer', [
                'email' => 'anna.smirnova@example.com',
                'name'  => 'Анна Смирнова',
                'phone' => '+79161234567',
            ])
            ->assertJsonPath('data.currency', 'RUB')
            ->assertJsonPath('data.items', [
                ['sku' => 'TSHIRT-BLK-M', 'name' => 'Футболка базовая, чёрная, M', 'quantity' => 3, 'price' => '1290.00', 'subtotal' => '3870.00'],
                ['sku' => 'CAP-NVY', 'name' => 'Бейсболка "Classic" / синяя', 'quantity' => 1, 'price' => '890.50', 'subtotal' => '890.50'],
            ])
            ->assertJsonPath('data.delivery_price', '349.00')
            ->assertJsonPath('data.discount', '200.00')
            ->assertJsonPath('data.total', '4909.50')
            ->assertJsonPath('data.shipment', [
                'carrier'         => 'СДЭК',
                'tracking_number' => '1098765432',
                'tracking_url'    => 'https://www.cdek.ru/ru/tracking?order_id=1098765432',
            ])
            ->assertJsonPath('data.cancelled_at', null)
            ->assertJsonPath('data.cancel_reason', null);
    }

    public function test_card_has_no_shipment_before_shipping(): void
    {
        $this->sendWebhook($this->exampleEvent('order-created'))->assertOk();

        $this->getJson('/api/orders/ord_7Q2KX9M4')
            ->assertOk()
            ->assertJsonPath('data.shipment', null)
            ->assertJsonPath('data.paid_at', null);
    }

    public function test_card_returns_marketplace_moments(): void
    {
        $this->sendWebhook($this->exampleEvent('order-created'))->assertOk();

        $order = $this->getJson('/api/orders/ord_7Q2KX9M4')->assertOk()->json('data');
        $listed = $this->getJson('/api/orders')->json('data.0');

        // Sent as 13:02:11+03:00: any offset is fine, the moment must match.
        $this->assertSame('2026-10-21T10:02:11+00:00', $order['created_at']);
        $this->assertSame('2026-10-21T10:02:11+00:00', $order['updated_at']);
        $this->assertSame('2026-10-21T10:02:11+00:00', $listed['created_at']);
    }

    public function test_card_history_is_chronological_when_events_arrive_out_of_order(): void
    {
        Mail::fake();
        $this->seedLetterTemplates();

        $this->sendWebhook($this->exampleEvent('order-shipped'))->assertOk();
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $this->sendWebhook($this->exampleEvent('order-created'))->assertOk();

        $this->getJson('/api/orders/ord_7Q2KX9M4')
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatusEnum::SHIPPED->value)
            ->assertJsonPath('data.history', [
                ['status' => 'created', 'at' => '2026-10-21T10:02:11+00:00'],
                ['status' => 'paid', 'at' => '2026-10-21T10:05:47+00:00'],
                ['status' => 'shipped', 'at' => '2026-10-22T06:40:03+00:00'],
            ]);
    }

    public function test_unknown_order_is_not_found(): void
    {
        $this->get('/api/orders/ord_missing')->assertNotFound()->assertJsonPath('message', 'Заказ не найден.');
    }
}
