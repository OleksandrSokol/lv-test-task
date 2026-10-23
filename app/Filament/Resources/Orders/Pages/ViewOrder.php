<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Actions\ViewLogsAction;
use App\Filament\Actions\ViewWebhooksAction;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewWebhooksAction::make()->outlined(),
            ViewLogsAction::make()->outlined(),
        ];
    }

    public function getTitle(): string
    {
        return __('admin.orders.sections.order') . ' №' . $this->getRecord()->number;
    }
}
