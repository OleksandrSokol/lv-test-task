<?php

namespace Tests\Feature\Marketplace;

use App\Modules\Core\Models\Log;
use App\Modules\Marketplace\Enums\WebhookStatusEnum;
use App\Modules\Marketplace\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SendsMarketplaceWebhooks;
use Tests\TestCase;

class WebhookIntakeTest extends TestCase
{
    use RefreshDatabase, SendsMarketplaceWebhooks;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLetterTemplates();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function examples(): array
    {
        return [
            'created'   => ['order-created'],
            'updated'   => ['order-updated'],
            'paid'      => ['order-paid'],
            'shipped'   => ['order-shipped'],
            'cancelled' => ['order-cancelled'],
        ];
    }

    #[DataProvider('examples')]
    public function test_signed_example_event_is_accepted(string $example): void
    {
        $this->sendWebhook($this->exampleEvent($example))
            ->assertOk()
            ->assertJson(['status' => 'applied']);

        $this->assertSame(WebhookStatusEnum::APPLIED, WebhookEvent::query()->sole()->status);
    }

    public function test_marketplace_signature_example_body_is_accepted_and_kept_byte_for_byte(): void
    {
        $body = file_get_contents(base_path('task/examples/signature-example-body.json'));

        $this->sendWebhook($body)->assertOk();

        $this->assertSame($body, WebhookEvent::query()->sole()->raw_body);
    }

    public function test_inbox_keeps_event_and_order_reference(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk();

        $inbox = WebhookEvent::query()->sole();

        $this->assertSame('evt_01JAXM4Q9T6V2K8R3N5W7Y0ZBC', $inbox->event_id);
        $this->assertSame('order.paid', $inbox->type);
        $this->assertSame('ord_7Q2KX9M4', $inbox->order_external_id);
        $this->assertSame('2026-10-21T10:05:48+00:00', $inbox->occurred_at->toIso8601String());
        $this->assertSame('ord_7Q2KX9M4', json_decode($inbox->raw_body, true)['data']['order']['id']);
    }

    public function test_redelivery_with_new_timestamp_is_a_no_op(): void
    {
        $body = $this->exampleEvent('order-paid');

        $this->sendWebhook($body)->assertOk()->assertJson(['status' => 'applied']);

        $this->travel(10)->minutes();

        $this->sendWebhook($body)->assertOk()->assertJson(['status' => 'duplicate']);

        $this->assertSame(1, WebhookEvent::query()->count());
        $this->assertSame(['webhook.applied', 'webhook.duplicate'], Log::query()->where('event', 'like', 'webhook.%')->orderBy('id')->pluck('event')->map->value->all());
    }

    public function test_older_snapshot_is_accepted_as_stale(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'))->assertOk()->assertJson(['status' => 'applied']);

        $this->sendWebhook($this->exampleEvent('order-created'))->assertOk()->assertJson(['status' => 'stale']);

        $this->assertSame(
            ['applied', 'stale'],
            WebhookEvent::query()->orderBy('id')->pluck('status')->map->value->all(),
        );
    }

    public function test_unknown_event_type_is_accepted_and_ignored(): void
    {
        $body = json_encode(['id' => 'evt_new_type', 'type' => 'order.refunded', 'created_at' => '2026-10-21T10:05:48+00:00', 'data' => ['anything' => true]]);

        $this->sendWebhook($body)->assertOk()->assertJson(['status' => 'ignored']);

        $this->assertSame(WebhookStatusEnum::IGNORED, WebhookEvent::query()->sole()->status);
    }

    public function test_wrong_signature_is_rejected(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'), ['X-Webhook-Signature' => 'sha256=' . str_repeat('0', 64)])
            ->assertUnauthorized();

        $this->assertSame(0, WebhookEvent::query()->count());
    }

    public function test_signature_is_checked_before_payload_validation(): void
    {
        $this->sendWebhook('{"id": "evt_1"}', ['X-Webhook-Signature' => 'sha256=' . str_repeat('0', 64)])
            ->assertUnauthorized();
    }

    public function test_missing_signature_is_rejected(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'), ['X-Webhook-Signature' => null])
            ->assertUnauthorized();
    }

    public function test_body_changed_after_signing_is_rejected(): void
    {
        $body = $this->exampleEvent('order-paid');

        $this->sendWebhook($body . ' ', signedBody: $body)->assertUnauthorized();
    }

    #[DataProvider('staleTimestamps')]
    public function test_timestamp_outside_window_is_rejected(int $shift): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'), timestamp: now()->getTimestamp() + $shift)
            ->assertUnauthorized();
    }

    /**
     * @return array<string, array{int}>
     */
    public static function staleTimestamps(): array
    {
        return [
            'past'   => [-301],
            'future' => [301],
        ];
    }

    public function test_header_id_must_match_body_id(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'), ['X-Webhook-Id' => 'evt_other'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id');

        $this->assertSame(0, WebhookEvent::query()->count());
    }

    #[DataProvider('invalidOrders')]
    public function test_invalid_order_snapshot_is_rejected(array $override, string $error): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid', ['data' => ['order' => $override]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($error);

        $this->assertSame(0, WebhookEvent::query()->count());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidOrders(): array
    {
        return [
            'unknown status'    => [['status' => 'lost'], 'data.order.status'],
            'unknown currency'  => [['currency' => 'GBP'], 'data.order.currency'],
            'amount format'     => [['total' => '4909.5'], 'data.order.total'],
            'date without zone' => [['updated_at' => '2026-10-21 13:05:47'], 'data.order.updated_at'],
            'zero quantity'     => [['items' => [['quantity' => 0]]], 'data.order.items.0.quantity'],
            'float quantity'    => [['items' => [['quantity' => 1.5]]], 'data.order.items.0.quantity'],
            'no order'          => [['id' => null, 'number' => null], 'data.order.id'],
            'no customer email' => [['customer' => ['email' => null]], 'data.order.customer.email'],
        ];
    }

    public function test_malformed_json_is_rejected(): void
    {
        $this->sendWebhook('{"id": "evt_1",', ['X-Webhook-Id' => 'evt_1'])
            ->assertUnprocessable();
    }

    public function test_missing_secret_is_a_server_error(): void
    {
        config(['marketplace.webhook_secret' => '']);

        $this->sendWebhook($this->exampleEvent('order-paid'))->assertInternalServerError();
    }

    public function test_timestamp_at_window_edge_is_accepted(): void
    {
        $this->sendWebhook($this->exampleEvent('order-paid'), timestamp: now()->getTimestamp() - 300)
            ->assertOk();
    }
}
