<?php

namespace Tests\Unit\Order;

use App\Modules\Core\Models\Log;
use App\Modules\Order\Actions\ApplyOrderSnapshotAction;
use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Enums\SnapshotOutcomeEnum;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderStatusHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsOrderSnapshots;
use Tests\TestCase;

class ApplyOrderSnapshotActionTest extends TestCase
{
    use BuildsOrderSnapshots, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLetterTemplates();
    }

    public function test_first_snapshot_creates_order_with_items_and_history(): void
    {
        $outcome = $this->apply('order-created');

        $order = $this->order();

        $this->assertSame(SnapshotOutcomeEnum::APPLIED, $outcome);
        $this->assertSame(OrderStatusEnum::CREATED, $order->status);
        $this->assertSame('3619.50', $order->total);
        $this->assertSame([['TSHIRT-BLK-M', 2], ['CAP-NVY', 1]], $order->items->map(fn ($i) => [$i->sku, $i->quantity])->all());
        $this->assertSame([['created', '2026-10-21T10:02:11+00:00']], $this->history());
        $this->assertSame(['order.created', 'order.status_changed'], $this->logEvents());
    }

    public function test_first_snapshot_may_already_be_paid(): void
    {
        $this->apply('order-paid');

        $this->assertSame(OrderStatusEnum::PAID, $this->order()->status);
        $this->assertSame([['created', '2026-10-21T10:02:11+00:00'], ['paid', '2026-10-21T10:05:47+00:00']], $this->history());
    }

    public function test_newer_snapshot_replaces_composition_and_totals(): void
    {
        $this->apply('order-created');

        $outcome = $this->apply('order-updated');

        $order = $this->order();

        $this->assertSame(SnapshotOutcomeEnum::APPLIED, $outcome);
        $this->assertSame('4909.50', $order->total);
        $this->assertSame(3, $order->items->first()->quantity);
        $this->assertSame('2026-10-21T10:03:30+00:00', $order->external_updated_at->toIso8601String());

        $update = Log::query()->where('event', 'order.updated')->sole();
        $this->assertEquals(['old' => '3619.50', 'new' => '4909.50'], $update->data['changes']['total']);
        $this->assertArrayHasKey('items', $update->data['changes']);
    }

    public function test_older_snapshot_does_not_change_state(): void
    {
        $this->apply('order-updated');

        $outcome = $this->apply('order-created');

        $this->assertSame(SnapshotOutcomeEnum::STALE, $outcome);
        $this->assertSame('4909.50', $this->order()->total);
        $this->assertContains('order.snapshot_stale', $this->logEvents());
    }

    public function test_snapshot_with_same_updated_at_is_stale(): void
    {
        $this->apply('order-paid');

        $outcome = $this->apply('order-paid', ['total' => '1.00']);

        $this->assertSame(SnapshotOutcomeEnum::STALE, $outcome);
        $this->assertSame('4909.50', $this->order()->total);
    }

    public function test_reverse_delivery_order_ends_in_latest_state_with_full_history(): void
    {
        foreach (['order-shipped', 'order-paid', 'order-updated', 'order-created'] as $example) {
            $this->apply($example);
        }

        $order = $this->order();

        $this->assertSame(OrderStatusEnum::SHIPPED, $order->status);
        $this->assertSame('СДЭК', $order->shipment_carrier);
        $this->assertSame([
            ['created', '2026-10-21T10:02:11+00:00'],
            ['paid', '2026-10-21T10:05:47+00:00'],
            ['shipped', '2026-10-22T06:40:03+00:00'],
        ], $this->history());
        $this->assertSame(1, Log::query()->where('event', 'order.status_changed')->count());
    }

    public function test_update_after_shipping_keeps_one_shipped_entry(): void
    {
        $this->apply('order-shipped');

        $this->apply('order-shipped', ['customer' => ['phone' => null], 'updated_at' => '2026-10-22T12:00:00+00:00']);

        $this->assertSame(['created', 'paid', 'shipped'], array_column($this->history(), 0));
        $this->assertSame('2026-10-22T06:40:03+00:00', $this->history()[2][1]);
    }

    public function test_later_snapshot_of_same_status_arriving_first_is_moved_back(): void
    {
        $this->apply('order-shipped', ['updated_at' => '2026-10-22T12:00:00+00:00']);

        $this->apply('order-shipped');

        $this->assertSame([
            ['created', '2026-10-21T10:02:11+00:00'],
            ['paid', '2026-10-21T10:05:47+00:00'],
            ['shipped', '2026-10-22T06:40:03+00:00'],
        ], $this->history());
    }

    public function test_manual_status_correction_is_applied_and_kept_in_history(): void
    {
        $this->apply('order-shipped');
        $this->apply('order-shipped', ['status' => 'delivered', 'updated_at' => '2026-10-23T09:00:00+00:00']);

        $this->apply('order-shipped', ['updated_at' => '2026-10-23T10:00:00+00:00']);

        $this->assertSame(OrderStatusEnum::SHIPPED, $this->order()->status);
        $this->assertSame(['created', 'paid', 'shipped', 'delivered', 'shipped'], array_column($this->history(), 0));
    }

    public function test_cancelled_paid_order_has_paid_and_cancelled_in_history(): void
    {
        $this->apply('order-cancelled');

        $this->assertSame([
            ['created', '2026-10-21T15:09:40+00:00'],
            ['paid', '2026-10-21T15:11:02+00:00'],
            ['cancelled', '2026-10-21T17:47:15+00:00'],
        ], $this->history());
    }

    private function apply(string $example, array $override = []): SnapshotOutcomeEnum
    {
        return app(ApplyOrderSnapshotAction::class)->handle($this->snapshot($example, $override));
    }

    private function order(): Order
    {
        return Order::query()->with('items')->sole();
    }

    /**
     * @return list<array{string, string}>
     */
    private function history(): array
    {
        return $this->order()->history
            ->map(fn (OrderStatusHistory $entry) => [$entry->status->value, $entry->changed_at->toIso8601String()])
            ->all();
    }

    /**
     * @return list<string>
     */
    private function logEvents(): array
    {
        return Log::query()->orderBy('id')->pluck('event')->map->value->all();
    }
}
