<?php

use App\Modules\Marketplace\Http\Controllers\WebhookController;
use App\Modules\Marketplace\Http\Middleware\VerifyMarketplaceSignature;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/marketplace', WebhookController::class)
    ->middleware(VerifyMarketplaceSignature::class)
    ->name('webhooks.marketplace');
