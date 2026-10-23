<?php

namespace Tests\Feature\Scenarios;

use App\Modules\Notification\Jobs\SendLetterJob;
use App\Modules\Notification\Mail\LetterMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SendsMarketplaceWebhooks;
use Tests\TestCase;

/**
 * Whole deliveries as the marketplace makes them, checked the way the admin sees the result:
 * signed POST /api/webhooks/marketplace in, GET /api/orders/{id} and the letters out.
 */
class WebhookDeliveryScenariosTest extends TestCase
{
    use RefreshDatabase, SendsMarketplaceWebhooks;

    private const ORDER = 'ord_7Q2KX9M4';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLetterTemplates();

        Mail::fake();
    }

    public function test_happy_path_ends_shipped_with_full_history_and_two_letters(): void
    {
        foreach (['order-created', 'order-updated', 'order-paid', 'order-shipped'] as $example) {
            $this->sendWebhook($this->exampleEvent($example))->assertOk()->assertJson(['status' => 'applied']);
        }

        $this->order()
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.total', '4909.50')
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.shipment.tracking_number', '1098765432')
            ->assertJsonPath('data.history', [
                ['status' => 'created', 'at' => '2026-10-21T10:02:11+00:00'],
                ['status' => 'paid', 'at' => '2026-10-21T10:05:47+00:00'],
                ['status' => 'shipped', 'at' => '2026-10-22T06:40:03+00:00'],
            ]);

        $this->assertLetters(['Заказ №MH-1048213 оплачен', 'Заказ №MH-1048213 передан в доставку']);
    }

    public function test_reverse_order_ends_in_latest_state_with_full_history(): void
    {
        $statuses = [];

        foreach (['order-shipped', 'order-paid', 'order-updated', 'order-created'] as $example) {
            $statuses[] = $this->sendWebhook($this->exampleEvent($example))->assertOk()->json('status');
        }

        $this->assertSame(['applied', 'stale', 'stale', 'stale'], $statuses);

        $this->order()
            ->assertJsonPath('data.status', 'shipped')
            // Composition of the freshest snapshot, not of the last one received.
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.total', '4909.50')
            ->assertJsonPath('data.updated_at', '2026-10-22T06:40:03+00:00')
            ->assertJsonPath('data.history', [
                ['status' => 'created', 'at' => '2026-10-21T10:02:11+00:00'],
                ['status' => 'paid', 'at' => '2026-10-21T10:05:47+00:00'],
                ['status' => 'shipped', 'at' => '2026-10-22T06:40:03+00:00'],
            ]);

        // "Paid" would contradict nothing, but a stale snapshot does not start letters.
        $this->assertLetters(['Заказ №MH-1048213 передан в доставку']);
    }

    public function test_every_event_delivered_twice_changes_nothing_the_second_time(): void
    {
        $examples = ['order-created', 'order-updated', 'order-paid', 'order-shipped'];

        foreach ($examples as $example) {
            $this->sendWebhook($this->exampleEvent($example))->assertOk();
        }

        $once = $this->order()->json('data');

        foreach ($examples as $example) {
            // A retry is signed anew: another timestamp, another signature, the same event id.
            $this->sendWebhook($this->exampleEvent($example), timestamp: now()->addSeconds(30)->getTimestamp())
                ->assertOk()
                ->assertJson(['status' => 'duplicate']);
        }

        $this->assertSame($once, $this->order()->json('data'));
        $this->assertLetters(['Заказ №MH-1048213 оплачен', 'Заказ №MH-1048213 передан в доставку']);
    }

    public function test_cancelled_paid_order_gets_refund_letter_and_late_paid_letter_is_withdrawn(): void
    {
        // Letters wait in the queue, as when the worker lags behind the webhooks.
        Queue::fake([SendLetterJob::class]);

        $this->sendWebhook($this->exampleEvent('order-created'))->assertOk();
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $this->sendWebhook($this->cancelled())->assertOk();

        Queue::pushed(SendLetterJob::class)->each(fn (SendLetterJob $job) => $job->handle());

        $this->order()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.paid_at', '2026-10-21T10:05:47+00:00')
            ->assertJsonPath('data.cancel_reason', 'Нет в наличии')
            ->assertJsonPath('data.history.*.status', ['created', 'paid', 'cancelled']);

        $this->assertLetters(['Заказ №MH-1048213 отменён']);
        Mail::assertSent(LetterMail::class, fn (LetterMail $mail) => str_contains($mail->letter->body, 'Деньги за заказ вернутся'));
    }

    public function test_manual_correction_back_to_shipped_is_applied_without_second_letter(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $this->sendWebhook($this->exampleEvent('order-shipped'))->assertOk();
        $this->sendWebhook($this->shipped('evt_delivered', 'order.delivered', 'delivered', '2026-10-23T09:00:00+00:00'))->assertOk();
        $this->sendWebhook($this->shipped('evt_corrected', 'order.shipped', 'shipped', '2026-10-23T12:00:00+00:00'))
            ->assertOk()
            ->assertJson(['status' => 'applied']);

        $this->order()
            ->assertJsonPath('data.status', 'shipped')
            ->assertJsonPath('data.history', [
                ['status' => 'created', 'at' => '2026-10-21T10:02:11+00:00'],
                ['status' => 'paid', 'at' => '2026-10-21T10:05:47+00:00'],
                ['status' => 'shipped', 'at' => '2026-10-22T06:40:03+00:00'],
                ['status' => 'delivered', 'at' => '2026-10-23T09:00:00+00:00'],
                ['status' => 'shipped', 'at' => '2026-10-23T12:00:00+00:00'],
            ]);

        $this->assertLetters(['Заказ №MH-1048213 оплачен', 'Заказ №MH-1048213 передан в доставку']);
    }

    public function test_unknown_event_type_is_accepted_and_leaves_order_alone(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();
        $before = $this->order()->json('data');

        // A type from the future, with a snapshot that would change everything.
        $this->sendWebhook($this->shipped('evt_new_type', 'order.refund_requested', 'cancelled', '2026-10-25T00:00:00+00:00'))
            ->assertOk()
            ->assertJson(['status' => 'ignored']);

        $this->assertSame($before, $this->order()->json('data'));
        $this->assertLetters(['Заказ №MH-1048213 оплачен']);
    }

    public function test_rejected_deliveries_change_nothing(): void
    {
        $this->sendWebhook($this->exampleEvent('order-created'))->assertOk();
        $before = $this->order()->json('data');

        $paid = $this->exampleEvent('order-paid');

        $this->sendWebhook($paid, ['X-Webhook-Signature' => 'sha256=' . str_repeat('0', 64)])->assertUnauthorized();
        $this->sendWebhook($paid, timestamp: now()->subMinutes(6)->getTimestamp())->assertUnauthorized();
        $this->sendWebhook($paid, timestamp: now()->addMinutes(6)->getTimestamp())->assertUnauthorized();

        $this->assertSame($before, $this->order()->json('data'));
        Mail::assertNothingSent();

        // Rejected deliveries left no trace: the marketplace's retry is processed as new.
        $this->sendWebhook($paid)->assertOk()->assertJson(['status' => 'applied']);
        $this->order()->assertJsonPath('data.status', 'paid');
    }

    public function test_dates_with_fractional_seconds_keep_their_moment(): void
    {
        $paid = fn (string $id, string $updatedAt) => $this->exampleEvent('order-paid', [
            'id'   => $id,
            'data' => ['order' => ['paid_at' => '2026-10-21T13:05:47.25+03:00', 'updated_at' => $updatedAt]],
        ]);

        $this->sendWebhook($paid('evt_paid', '2026-10-21T13:05:47.25+03:00'))->assertOk()->assertJson(['status' => 'applied']);
        // Same second, later fraction: newer.
        $this->sendWebhook($paid('evt_paid_later', '2026-10-21T13:05:47.5+03:00'))->assertOk()->assertJson(['status' => 'applied']);
        // The very same moment again: not newer, and the history keeps one paid entry.
        $this->sendWebhook($paid('evt_paid_again', '2026-10-21T13:05:47.5+03:00'))->assertOk()->assertJson(['status' => 'stale']);

        $this->order()
            ->assertJsonPath('data.paid_at', '2026-10-21T10:05:47.25+00:00')
            ->assertJsonPath('data.updated_at', '2026-10-21T10:05:47.5+00:00')
            ->assertJsonPath('data.created_at', '2026-10-21T10:02:11+00:00')
            ->assertJsonPath('data.history', [
                ['status' => 'created', 'at' => '2026-10-21T10:02:11+00:00'],
                ['status' => 'paid', 'at' => '2026-10-21T10:05:47.25+00:00'],
            ]);
    }

    private function order(): TestResponse
    {
        return $this->getJson('/api/orders/' . self::ORDER)->assertOk();
    }

    /**
     * Exactly these letters, each once, to the customer.
     *
     * @param  list<string>  $subjects
     */
    private function assertLetters(array $subjects): void
    {
        Mail::assertSent(LetterMail::class, count($subjects));

        foreach ($subjects as $subject) {
            Mail::assertSent(LetterMail::class, fn (LetterMail $mail) => $mail->hasSubject($subject)
                && $mail->hasTo('anna.smirnova@example.com'));
        }
    }

    private function cancelled(): string
    {
        return $this->exampleEvent('order-paid', [
            'id'   => 'evt_cancelled',
            'type' => 'order.cancelled',
            'data' => ['order' => [
                'status'        => 'cancelled',
                'cancelled_at'  => '2026-10-21T14:00:00+03:00',
                'cancel_reason' => 'Нет в наличии',
                'updated_at'    => '2026-10-21T14:00:00+03:00',
            ]],
        ]);
    }

    /**
     * The shipped example moved on: another event with a newer snapshot.
     */
    private function shipped(string $id, string $type, string $status, string $updatedAt): string
    {
        return $this->exampleEvent('order-shipped', [
            'id'   => $id,
            'type' => $type,
            'data' => ['order' => ['status' => $status, 'updated_at' => $updatedAt]],
        ]);
    }
}
