<?php

namespace Tests\Unit\Order;

use App\Modules\Order\Dtos\OrderSnapshotData;
use App\Modules\Order\Enums\CurrencyEnum;
use App\Modules\Order\Enums\OrderStatusEnum;
use Tests\TestCase;

class OrderSnapshotDataTest extends TestCase
{
    public function test_example_snapshot_is_parsed(): void
    {
        $snapshot = $this->snapshot('order-shipped');

        $this->assertSame('ord_7Q2KX9M4', $snapshot->externalId);
        $this->assertSame(OrderStatusEnum::SHIPPED, $snapshot->status);
        $this->assertSame(CurrencyEnum::RUB, $snapshot->currency);
        $this->assertSame('anna.smirnova@example.com', $snapshot->customer->email);
        $this->assertCount(2, $snapshot->items);
        $this->assertSame(3, $snapshot->items[0]->quantity);
        $this->assertSame('4909.50', $snapshot->total);
        $this->assertSame('1098765432', $snapshot->shipment->trackingNumber);
        $this->assertNull($snapshot->cancelledAt);
    }

    public function test_dates_are_converted_to_utc_keeping_the_moment(): void
    {
        $snapshot = $this->snapshot('order-paid');

        $this->assertSame('2026-10-21T10:02:11+00:00', $snapshot->createdAt->toIso8601String());
        $this->assertSame('2026-10-21T10:05:47+00:00', $snapshot->paidAt->toIso8601String());
        $this->assertSame('UTC', $snapshot->updatedAt->getTimezone()->getName());
    }

    public function test_fractional_seconds_are_accepted_and_kept(): void
    {
        $event = json_decode(file_get_contents(base_path('task/examples/order-paid.json')), true);
        $event['data']['order']['updated_at'] = '2026-10-21T13:05:47.25+03:00';

        $snapshot = OrderSnapshotData::validateAndCreate($event['data']['order']);

        $this->assertSame('2026-10-21 10:05:47.250000', $snapshot->updatedAt->format('Y-m-d H:i:s.u'));
    }

    private function snapshot(string $example): OrderSnapshotData
    {
        $event = json_decode(file_get_contents(base_path("task/examples/{$example}.json")), true);

        return OrderSnapshotData::validateAndCreate($event['data']['order']);
    }
}
