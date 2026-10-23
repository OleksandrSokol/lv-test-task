<?php

namespace Tests\Concerns;

use App\Modules\Marketplace\Support\WebhookSignature;
use Illuminate\Testing\TestResponse;

/**
 * Sends webhooks the way the marketplace does: signs the raw body bytes.
 */
trait SendsMarketplaceWebhooks
{
    /**
     * Body of an example event from task/examples, optionally changed.
     *
     * @param  array<string, mixed>  $override  merged recursively into the event
     */
    protected function exampleEvent(string $name, array $override = []): string
    {
        $event = json_decode(file_get_contents(base_path("task/examples/{$name}.json")), true);

        return json_encode(array_replace_recursive($event, $override), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<string, string|null>  $headers  overrides the signed headers; null removes one
     * @param  int|null  $timestamp  X-Webhook-Timestamp, now by default
     * @param  string|null  $signedBody  what the signature is computed over, the sent body by default
     */
    protected function sendWebhook(string $body, array $headers = [], ?int $timestamp = null, ?string $signedBody = null): TestResponse
    {
        $timestamp = (string) ($timestamp ?? now()->getTimestamp());
        $signature = new WebhookSignature((string) config('marketplace.webhook_secret'));

        $headers = array_filter([
            'X-Webhook-Id'        => json_decode($body, true)['id'] ?? null,
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Signature' => $signature->sign($timestamp, $signedBody ?? $body),
            ...$headers,
        ], fn (?string $value) => $value !== null);

        $server = ['CONTENT_TYPE' => 'application/json'];

        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $this->call('POST', '/api/webhooks/marketplace', server: $server, content: $body);
    }
}
