<?php

namespace App\Modules\Marketplace\Support;

/**
 * Marketplace webhook signature: `sha256=` + HMAC-SHA256 of "{timestamp}.{raw body}".
 */
final readonly class WebhookSignature
{
    public function __construct(
        private string $secret,
    ) {}

    public function sign(string $timestamp, string $body): string
    {
        return 'sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $this->secret);
    }

    public function verify(string $signature, string $timestamp, string $body): bool
    {
        return hash_equals($this->sign($timestamp, $body), strtolower($signature));
    }
}
