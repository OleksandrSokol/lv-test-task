<?php

namespace App\Filament\Resources\WebhookEvents;

use App\Filament\Actions\ViewLogsAction;
use App\Filament\Infolists\JsonEntry;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\WebhookEvents\Pages\ManageWebhookEvents;
use App\Modules\Marketplace\Enums\WebhookEventTypeEnum;
use App\Modules\Marketplace\Enums\WebhookStatusEnum;
use App\Modules\Marketplace\Models\WebhookEvent;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The marketplace's events as received: the inbox, read only.
 */
class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    // Russian labels: only the first word is capitalised.
    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return __('admin.webhook_events.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.webhook_events.plural');
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
            ->modifyQueryUsing(fn ($query) => $query->with('order'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.webhook_events.fields.created_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('admin.webhook_events.fields.type'))
                    ->formatStateUsing(fn (string $state) => self::typeLabel($state)),

                TextColumn::make('status')
                    ->label(__('admin.webhook_events.fields.status'))
                    ->badge()
                    ->color(fn (WebhookStatusEnum $state) => $state->color())
                    ->formatStateUsing(fn (WebhookStatusEnum $state) => $state->label()),

                TextColumn::make('order.number')
                    ->label(__('admin.webhook_events.fields.order'))
                    ->url(fn (WebhookEvent $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null)
                    ->placeholder('—'),

                TextColumn::make('order_external_id')
                    ->label(__('admin.webhook_events.fields.order_external_id'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('event_id')
                    ->label(__('admin.webhook_events.fields.event_id'))
                    ->searchable()
                    ->copyable()
                    ->fontFamily(FontFamily::Mono),

                TextColumn::make('occurred_at')
                    ->label(__('admin.webhook_events.fields.occurred_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.webhook_events.fields.status'))
                    // A duplicate is never stored.
                    ->options(collect(WebhookStatusEnum::options())->except(WebhookStatusEnum::DUPLICATE->value)->all()),

                SelectFilter::make('type')
                    ->label(__('admin.webhook_events.fields.type'))
                    // Known types plus any new ones the marketplace has already sent.
                    ->options(fn () => WebhookEvent::query()
                        ->distinct()
                        ->pluck('type')
                        ->mapWithKeys(fn (string $type) => [$type => self::typeLabel($type)])
                        ->union(WebhookEventTypeEnum::options())
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->slideOver()
                    ->modalWidth('3xl')
                    ->schema(self::viewSchema())
                    ->extraModalFooterActions(fn (WebhookEvent $record) => [
                        ViewLogsAction::make()->record($record)->outlined(),
                    ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWebhookEvents::route('/'),
        ];
    }

    private static function viewSchema(): array
    {
        return [
            Section::make()
                ->description(__('admin.webhook_events.duplicates_hint'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('event_id')
                                ->label(__('admin.webhook_events.fields.event_id'))
                                ->copyable()
                                ->fontFamily(FontFamily::Mono),

                            TextEntry::make('type')
                                ->label(__('admin.webhook_events.fields.type'))
                                ->formatStateUsing(fn (string $state) => self::typeLabel($state)),

                            TextEntry::make('status')
                                ->label(__('admin.webhook_events.fields.status'))
                                ->badge()
                                ->color(fn (WebhookStatusEnum $state) => $state->color())
                                ->formatStateUsing(fn (WebhookStatusEnum $state) => $state->label()),

                            TextEntry::make('order_external_id')
                                ->label(__('admin.webhook_events.fields.order'))
                                ->url(fn (WebhookEvent $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null)
                                ->placeholder('—'),

                            TextEntry::make('occurred_at')
                                ->label(__('admin.webhook_events.fields.occurred_at'))
                                ->dateTime('d.m.Y H:i:s'),

                            TextEntry::make('created_at')
                                ->label(__('admin.webhook_events.fields.created_at'))
                                ->dateTime('d.m.Y H:i:s'),
                        ]),
                ]),

            Section::make(__('admin.webhook_events.fields.raw_body'))
                ->description(__('admin.webhook_events.raw_body_hint'))
                ->schema([
                    JsonEntry::make('raw_body')
                        ->hiddenLabel()
                        // Shown formatted, copied exactly as received.
                        ->copyable()
                        ->copyableState(fn (WebhookEvent $record) => $record->raw_body),
                ]),
        ];
    }

    /**
     * A type the marketplace added after us has no label: it is marked unknown, with its name.
     */
    private static function typeLabel(string $type): string
    {
        return WebhookEventTypeEnum::tryFrom($type)?->label()
            ?? __('admin.webhook_events.unknown_type', ['type' => $type]);
    }
}
