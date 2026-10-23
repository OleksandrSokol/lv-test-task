<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Enums\LogLetterStatusEnum;
use App\Modules\Order\Enums\OrderStatusEnum;
use App\Modules\Order\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Orders as received from the marketplace: read only, the marketplace owns them.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    // Russian labels: only the first word is capitalised.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getModelLabel(): string
    {
        return __('admin.orders.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.orders.plural');
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
            // The marketplace's placement time, as in the API.
            ->defaultSort('external_created_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label(__('admin.orders.fields.number'))
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('admin.orders.fields.status'))
                    ->badge()
                    ->color(fn (OrderStatusEnum $state) => $state->color())
                    ->formatStateUsing(fn (OrderStatusEnum $state) => $state->label()),

                TextColumn::make('customer_name')
                    ->label(__('admin.orders.fields.customer_name')),

                TextColumn::make('customer_email')
                    ->label(__('admin.orders.fields.customer_email'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('total')
                    ->label(__('admin.orders.fields.total'))
                    ->money(fn (Order $record) => $record->currency->value)
                    ->alignEnd(),

                TextColumn::make('external_created_at')
                    ->label(__('admin.orders.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.orders.fields.status'))
                    ->options(OrderStatusEnum::options()),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
            ], position: RecordActionsPosition::BeforeColumns);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(2)
                    ->schema([
                        self::orderSection(),
                        self::customerSection(),
                    ]),
                self::shipmentSection(),
                self::itemsSection(),
                self::historySection(),
                self::lettersSection(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view'  => ViewOrder::route('/{record}'),
        ];
    }

    private static function orderSection(): Section
    {
        return Section::make(__('admin.orders.sections.order'))
            ->columns(2)
            ->schema([
                TextEntry::make('number')
                    ->label(__('admin.orders.fields.number')),

                TextEntry::make('external_id')
                    ->label(__('admin.orders.fields.external_id'))
                    ->copyable(),

                TextEntry::make('status')
                    ->label(__('admin.orders.fields.status'))
                    ->badge()
                    ->color(fn (OrderStatusEnum $state) => $state->color())
                    ->formatStateUsing(fn (OrderStatusEnum $state) => $state->label()),

                TextEntry::make('total')
                    ->label(__('admin.orders.fields.total'))
                    ->money(fn (Order $record) => $record->currency->value),

                TextEntry::make('delivery_price')
                    ->label(__('admin.orders.fields.delivery_price'))
                    ->money(fn (Order $record) => $record->currency->value),

                TextEntry::make('discount')
                    ->label(__('admin.orders.fields.discount'))
                    ->money(fn (Order $record) => $record->currency->value),

                TextEntry::make('external_created_at')
                    ->label(__('admin.orders.fields.created_at'))
                    ->dateTime('d.m.Y H:i:s'),

                TextEntry::make('external_updated_at')
                    ->label(__('admin.orders.fields.updated_at'))
                    ->dateTime('d.m.Y H:i:s'),

                TextEntry::make('paid_at')
                    ->label(__('admin.orders.fields.paid_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->placeholder('—'),

                TextEntry::make('created_at')
                    ->label(__('admin.orders.fields.received_at'))
                    ->dateTime('d.m.Y H:i:s'),

                TextEntry::make('cancelled_at')
                    ->label(__('admin.orders.fields.cancelled_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->visible(fn (Order $record) => $record->cancelled_at !== null),

                TextEntry::make('cancel_reason')
                    ->label(__('admin.orders.fields.cancel_reason'))
                    ->placeholder('—')
                    ->visible(fn (Order $record) => $record->status === OrderStatusEnum::CANCELLED),
            ]);
    }

    private static function customerSection(): Section
    {
        return Section::make(__('admin.orders.sections.customer'))
            ->schema([
                TextEntry::make('customer_name')
                    ->label(__('admin.orders.fields.customer_name')),

                TextEntry::make('customer_email')
                    ->label(__('admin.orders.fields.customer_email'))
                    ->copyable(),

                TextEntry::make('customer_phone')
                    ->label(__('admin.orders.fields.customer_phone'))
                    ->placeholder('—'),
            ]);
    }

    private static function shipmentSection(): Section
    {
        return Section::make(__('admin.orders.sections.shipment'))
            ->columns(2)
            ->schema([
                TextEntry::make('shipment_carrier')
                    ->label(__('admin.orders.fields.carrier'))
                    ->placeholder(__('admin.orders.no_shipment')),

                TextEntry::make('shipment_tracking_number')
                    ->label(__('admin.orders.fields.tracking'))
                    ->url(fn (Order $record) => $record->shipment_tracking_url, shouldOpenInNewTab: true)
                    ->placeholder('—'),
            ]);
    }

    private static function itemsSection(): Section
    {
        return Section::make(__('admin.orders.sections.items'))
            ->schema([
                RepeatableEntry::make('items')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make(__('admin.orders.fields.sku')),
                        TableColumn::make(__('admin.orders.fields.name')),
                        TableColumn::make(__('admin.orders.fields.quantity')),
                        TableColumn::make(__('admin.orders.fields.price')),
                        TableColumn::make(__('admin.orders.fields.subtotal')),
                    ])
                    ->schema([
                        TextEntry::make('sku'),
                        TextEntry::make('name'),
                        TextEntry::make('quantity'),
                        TextEntry::make('price')->numeric(decimalPlaces: 2),
                        TextEntry::make('subtotal')->numeric(decimalPlaces: 2),
                    ]),
            ]);
    }

    private static function historySection(): Section
    {
        return Section::make(__('admin.orders.sections.history'))
            ->schema([
                RepeatableEntry::make('history')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make(__('admin.orders.fields.status')),
                        TableColumn::make(__('admin.orders.fields.changed_at')),
                    ])
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (OrderStatusEnum $state) => $state->color())
                            ->formatStateUsing(fn (OrderStatusEnum $state) => $state->label()),
                        TextEntry::make('changed_at')
                            ->dateTime('d.m.Y H:i:s'),
                    ]),
            ]);
    }

    private static function lettersSection(): Section
    {
        return Section::make(__('admin.orders.sections.letters'))
            ->schema([
                RepeatableEntry::make('letters')
                    ->hiddenLabel()
                    ->placeholder(__('admin.orders.no_letters'))
                    ->table([
                        TableColumn::make(__('admin.log_letters.fields.template_key')),
                        TableColumn::make(__('admin.log_letters.fields.receiver')),
                        TableColumn::make(__('admin.log_letters.fields.status')),
                        TableColumn::make(__('admin.log_letters.fields.error_message')),
                        TableColumn::make(__('admin.log_letters.fields.updated_at')),
                    ])
                    ->schema([
                        TextEntry::make('template_key')
                            ->formatStateUsing(fn (LetterEnum $state) => $state->label()),
                        TextEntry::make('receiver'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (LogLetterStatusEnum $state) => $state->color())
                            ->formatStateUsing(fn (LogLetterStatusEnum $state) => $state->label()),
                        TextEntry::make('error_message')
                            ->placeholder('—'),
                        TextEntry::make('updated_at')
                            ->dateTime('d.m.Y H:i:s'),
                    ]),
            ]);
    }
}
