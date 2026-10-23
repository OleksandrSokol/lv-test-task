<?php

namespace App\Filament\Resources\LogLetters;

use App\Filament\Actions\ViewLogsAction;
use App\Filament\Resources\LogLetters\Pages\ManageLogLetters;
use App\Filament\Resources\Orders\OrderResource;
use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Enums\LogLetterStatusEnum;
use App\Modules\Notification\Models\LogLetter;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * What was sent to customers, or why it was not: read only.
 */
class LogLetterResource extends Resource
{
    protected static ?string $model = LogLetter::class;

    // Russian labels: only the first word is capitalised.
    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return __('admin.log_letters.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.log_letters.plural');
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
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.log_letters.fields.created_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                TextColumn::make('template_key')
                    ->label(__('admin.log_letters.fields.template_key'))
                    ->formatStateUsing(fn (LetterEnum $state) => $state->label()),

                TextColumn::make('order.number')
                    ->label(__('admin.log_letters.fields.order'))
                    ->url(fn (LogLetter $record) => OrderResource::getUrl('view', ['record' => $record->order])),

                TextColumn::make('receiver')
                    ->label(__('admin.log_letters.fields.receiver'))
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('admin.log_letters.fields.status'))
                    ->badge()
                    ->color(fn (LogLetterStatusEnum $state) => $state->color())
                    ->formatStateUsing(fn (LogLetterStatusEnum $state) => $state->label()),

                TextColumn::make('error_message')
                    ->label(__('admin.log_letters.fields.error_message'))
                    ->limit(60)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.log_letters.fields.status'))
                    ->options(LogLetterStatusEnum::options()),

                SelectFilter::make('template_key')
                    ->label(__('admin.log_letters.fields.template_key'))
                    ->options(LetterEnum::options()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->slideOver()
                    ->modalWidth('3xl')
                    ->schema(self::viewSchema())
                    ->extraModalFooterActions(fn (LogLetter $record) => [
                        ViewLogsAction::make()->record($record)->outlined(),
                    ]),
            ], position: RecordActionsPosition::BeforeColumns);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLogLetters::route('/'),
        ];
    }

    private static function viewSchema(): array
    {
        return [
            Section::make()
                ->columns(2)
                ->schema([
                    TextEntry::make('template_key')
                        ->label(__('admin.log_letters.fields.template_key'))
                        ->formatStateUsing(fn (LetterEnum $state) => $state->label()),

                    TextEntry::make('status')
                        ->label(__('admin.log_letters.fields.status'))
                        ->badge()
                        ->color(fn (LogLetterStatusEnum $state) => $state->color())
                        ->formatStateUsing(fn (LogLetterStatusEnum $state) => $state->label()),

                    TextEntry::make('receiver')
                        ->label(__('admin.log_letters.fields.receiver')),

                    TextEntry::make('sender')
                        ->label(__('admin.log_letters.fields.sender'))
                        ->placeholder(config('mail.from.address')),

                    TextEntry::make('created_at')
                        ->label(__('admin.log_letters.fields.created_at'))
                        ->dateTime('d.m.Y H:i:s'),

                    TextEntry::make('updated_at')
                        ->label(__('admin.log_letters.fields.updated_at'))
                        ->dateTime('d.m.Y H:i:s'),

                    TextEntry::make('error_message')
                        ->label(__('admin.log_letters.fields.error_message'))
                        ->columnSpanFull()
                        ->visible(fn (LogLetter $record) => filled($record->error_message)),
                ]),

            Section::make()
                ->schema([
                    TextEntry::make('subject')
                        ->label(__('admin.log_letters.fields.subject')),

                    TextEntry::make('body')
                        ->label(__('admin.log_letters.fields.body'))
                        // Inline: Filament's prebuilt CSS has no `whitespace-pre-*` utilities.
                        ->extraAttributes(['style' => 'white-space: pre-line']),
                ]),
        ];
    }
}
