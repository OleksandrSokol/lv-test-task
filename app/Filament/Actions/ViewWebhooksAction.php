<?php

namespace App\Filament\Actions;

use App\Filament\Resources\WebhookEvents\WebhookEventResource;
use App\Modules\Order\Models\Order;
use Filament\Actions\Action;

/**
 * Opens the webhook inbox filtered to the order: every event the marketplace sent about it.
 */
class ViewWebhooksAction
{
    public static function make(): Action
    {
        return Action::make('view_webhooks')
            ->label(__('admin.actions.view_webhooks'))
            ->icon('heroicon-o-arrow-down-tray')
            ->url(fn (Order $record) => WebhookEventResource::getUrl('index', [
                'filters[order][order_external_id]' => $record->external_id,
            ]), true);
    }
}
