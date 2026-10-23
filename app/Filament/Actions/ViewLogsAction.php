<?php

namespace App\Filament\Actions;

use App\Filament\Resources\Logs\LogResource;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Opens the activity log filtered to the record.
 */
class ViewLogsAction
{
    public static function make(): Action
    {
        return Action::make('view_logs')
            ->label(__('admin.actions.view_logs'))
            ->icon('heroicon-o-clock')
            ->url(fn (Model $record) => LogResource::getUrl('index', [
                'filters[subject_type][value]'    => $record->getMorphClass(),
                'filters[subject_id][subject_id]' => $record->getKey(),
            ]), true);
    }
}
