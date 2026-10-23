<?php

namespace Tests\Unit\Marketplace;

use App\Modules\Marketplace\Support\WebhookSignature;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    public function test_signature_matches_marketplace_example(): void
    {
        $body = file_get_contents(base_path('task/examples/signature-example-body.json'));

        $signature = new WebhookSignature('whsec_test_5f1d8c2a9b');

        $this->assertSame(
            'sha256=4d4f2d50f98596ab503d29dc96db6fda247e3c482bff130bd97353564cb163f7',
            $signature->sign('1792577149', $body),
        );
    }

    public function test_signature_does_not_verify_changed_body(): void
    {
        $signature = new WebhookSignature('secret');
        $header = $signature->sign('1792577149', '{"id":"evt_1"}');

        $this->assertTrue($signature->verify($header, '1792577149', '{"id":"evt_1"}'));
        $this->assertFalse($signature->verify($header, '1792577149', '{"id": "evt_1"}'));
        $this->assertFalse($signature->verify($header, '1792577150', '{"id":"evt_1"}'));
    }
}
