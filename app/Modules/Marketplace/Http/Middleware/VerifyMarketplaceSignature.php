<?php

namespace App\Modules\Marketplace\Http\Middleware;

use App\Modules\Marketplace\Support\WebhookSignature;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects a webhook whose signature does not match the raw body or whose timestamp is too old.
 */
class VerifyMarketplaceSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('marketplace.webhook_secret');

        if ($secret === '') {
            throw new RuntimeException('MARKETPLACE_WEBHOOK_SECRET is not set.');
        }

        $timestamp = (string) $request->header('X-Webhook-Timestamp');

        if (!$this->isFresh($timestamp)) {
            $this->reject($request, __('marketplace.webhook.invalid_timestamp'));
        }

        // The signature covers the body exactly as received, byte for byte.
        $signature = new WebhookSignature($secret);

        if (!$signature->verify((string) $request->header('X-Webhook-Signature'), $timestamp, $request->getContent())) {
            $this->reject($request, __('marketplace.webhook.invalid_signature'));
        }

        return $next($request);
    }

    private function isFresh(string $timestamp): bool
    {
        return ctype_digit($timestamp)
            && abs(now()->getTimestamp() - (int) $timestamp) <= config('marketplace.webhook_tolerance');
    }

    private function reject(Request $request, string $reason): never
    {
        Log::warning('Marketplace webhook rejected', [
            'reason'   => $reason,
            'event_id' => $request->header('X-Webhook-Id'),
            'ip'       => $request->ip(),
        ]);

        abort(Response::HTTP_UNAUTHORIZED, $reason);
    }
}
