<?php

namespace App\Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Marketplace\Actions\ReceiveWebhookAction;
use App\Modules\Marketplace\Http\Requests\MarketplaceWebhookRequest;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    public function __invoke(MarketplaceWebhookRequest $request, ReceiveWebhookAction $action): JsonResponse
    {
        $status = $action->handle($request->event(), $request->snapshot(), $request->getContent());

        return response()->json(['status' => $status->value]);
    }
}
