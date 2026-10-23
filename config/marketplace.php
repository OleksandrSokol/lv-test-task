<?php

return [
    // HMAC-SHA256 secret the marketplace signs webhooks with.
    'webhook_secret' => env('MARKETPLACE_WEBHOOK_SECRET'),

    // Maximum allowed difference between X-Webhook-Timestamp and now, in seconds.
    'webhook_tolerance' => 300,
];
