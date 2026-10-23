<?php

namespace App\Filament\Actions;

use App\Filament\Resources\Logs\LogResource;
use App\Modules\Order\Models\Order;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Opens the activity log filtered to the record; for an order — to everything about it,
 * its letters and webhooks included.
 */
class ViewLogsAction
{
    public static function make(): Action
    {
        return Action::make('view_logs')
            ->label(__('admin.actions.view_logs'))
            ->icon('heroicon-o-clock')
            ->url(fn (Model $record) => LogResource::getUrl('index', $record instanceof Order
                ? ['filters[order][order_id]' => $record->getKey()]
                : [
                    'filters[subject_type][value]'    => $record->getMorphClass(),
                    'filters[subject_id][subject_id]' => $record->getKey(),
                ]), true);
    }
}
