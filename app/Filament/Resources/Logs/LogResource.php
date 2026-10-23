<?php

namespace App\Filament\Resources\Logs;

use App\Filament\Infolists\JsonEntry;
use App\Filament\Resources\Logs\Pages\ManageLogs;
use App\Modules\Core\Enums\ActivitySourceEnum;
use App\Modules\Core\Models\Log;
use App\Modules\Order\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * The activity log: what happened to orders, letters and webhooks. Read only.
 */
class LogResource extends Resource
{
    protected static ?string $model = Log::class;

    // Russian labels: only the first word is capitalised.
    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return __('admin.logs.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.logs.plural');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            // Filament adds the key as a tie-breaker: many rows share a second.
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.logs.fields.created_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('event')
                    ->label(__('admin.logs.fields.event'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => self::eventLabel($state)),

                TextColumn::make('subject_type')
                    ->label(__('admin.logs.fields.subject_type'))
                    ->formatStateUsing(fn (string $state) => self::subjectLabel($state)),

                TextColumn::make('subject_id')
                    ->label(__('admin.logs.fields.subject_id')),

                TextColumn::make('source')
                    ->label(__('admin.logs.fields.source'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (ActivitySourceEnum $state) => $state->label()),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label(__('admin.logs.fields.event'))
                    ->options(__('admin.logs.events')),

                SelectFilter::make('source')
                    ->label(__('admin.logs.fields.source'))
                    ->options(ActivitySourceEnum::options()),

                SelectFilter::make('subject_type')
                    ->label(__('admin.logs.fields.subject_type'))
                    ->options(fn () => Log::query()
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->pluck('subject_type')
                        ->mapWithKeys(fn (string $type) => [$type => self::subjectLabel($type)])
                        ->all()),

                // Everything about one order: its own events, its letters and its webhooks.
                Filter::make('order')
                    ->schema([
                        Select::make('order_id')
                            ->label(__('admin.logs.fields.order'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search) => Order::query()
                                ->where('number', 'ilike', "%{$search}%")
                                ->orWhere('external_id', 'ilike', "%{$search}%")
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Order $order) => [$order->id => self::orderLabel($order)])
                                ->all())
                            ->getOptionLabelUsing(fn ($value) => ($order = Order::query()->find($value)) ? self::orderLabel($order) : null),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['order_id'] ?? null,
                        fn (Builder $query, $id) => $query->whereRaw("data->>'order_id' = ?", [(string) $id]),
                    ))
                    ->indicateUsing(fn (array $data) => filled($data['order_id'] ?? null)
                        ? __('admin.logs.order_indicator', ['order' => ($order = Order::query()->find($data['order_id'])) ? self::orderLabel($order) : $data['order_id']])
                        : null),

                Filter::make('subject_id')
                    ->schema([
                        TextInput::make('subject_id')
                            ->label(__('admin.logs.fields.subject_id'))
                            ->integer(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['subject_id'] ?? null,
                        fn (Builder $query, $id) => $query->where('subject_id', $id),
                    ))
                    ->indicateUsing(fn (array $data) => filled($data['subject_id'] ?? null)
                        ? __('admin.logs.subject_indicator', ['id' => $data['subject_id']])
                        : null),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label(__('admin.logs.fields.from')),
                        DatePicker::make('until')->label(__('admin.logs.fields.until')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data) => filled($data['from'] ?? null) || filled($data['until'] ?? null)
                        ? __('admin.logs.period_indicator', ['from' => $data['from'] ?? '…', 'until' => $data['until'] ?? '…'])
                        : null),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->slideOver()
                    ->modalWidth('3xl')
                    ->schema(self::viewSchema()),
            ], position: RecordActionsPosition::BeforeColumns);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLogs::route('/'),
        ];
    }

    private static function viewSchema(): array
    {
        return [
            Section::make()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('event')
                                ->label(__('admin.logs.fields.event'))
                                ->badge()
                                ->formatStateUsing(fn (string $state) => self::eventLabel($state)),

                            TextEntry::make('created_at')
                                ->label(__('admin.logs.fields.created_at'))
                                ->dateTime('d.m.Y H:i:s'),

                            TextEntry::make('subject_type')
                                ->label(__('admin.logs.fields.subject_type'))
                                ->formatStateUsing(fn (string $state) => self::subjectLabel($state))
                                ->placeholder('—'),

                            TextEntry::make('subject_id')
                                ->label(__('admin.logs.fields.subject_id'))
                                ->placeholder('—'),

                            TextEntry::make('source')
                                ->label(__('admin.logs.fields.source'))
                                ->badge()
                                ->color('gray')
                                ->formatStateUsing(fn (ActivitySourceEnum $state) => $state->label()),
                        ]),
                ]),

            Section::make(__('admin.logs.fields.data'))
                ->schema([
                    // `changes` holds old/new pairs, items included.
                    JsonEntry::make('data')
                        ->hiddenLabel()
                        ->placeholder('—'),
                ]),
        ];
    }

    private static function orderLabel(Order $order): string
    {
        return "{$order->number} ({$order->external_id})";
    }

    private static function eventLabel(string $event): string
    {
        // Event names contain dots, so they cannot be a translation key path.
        return __('admin.logs.events')[$event] ?? $event;
    }

    private static function subjectLabel(string $type): string
    {
        $key = 'admin.logs.subjects.' . Str::snake(class_basename($type));

        return Lang::has($key) ? __($key) : class_basename($type);
    }
}
