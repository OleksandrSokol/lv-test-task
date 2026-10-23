<?php

namespace App\Filament\Resources\LetterTemplates;

use App\Filament\Resources\LetterTemplates\Pages\ManageLetterTemplates;
use App\Modules\Notification\Enums\LetterEnum;
use App\Modules\Notification\Models\LetterTemplate;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Texts of the customer letters. One template per letter type, so they are only edited.
 */
class LetterTemplateResource extends Resource
{
    protected static ?string $model = LetterTemplate::class;

    // Russian labels: only the first word is capitalised.
    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return __('admin.letter_templates.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.letter_templates.plural');
    }

    public static function canCreate(): bool
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('key')
                    ->label(__('admin.letter_templates.fields.key'))
                    ->options(LetterEnum::options())
                    ->disabled()
                    ->columnSpanFull(),

                TextInput::make('sender')
                    ->label(__('admin.letter_templates.fields.sender'))
                    ->email()
                    ->helperText(__('admin.letter_templates.sender_helper')),

                TextInput::make('sender_name')
                    ->label(__('admin.letter_templates.fields.sender_name')),

                TextInput::make('subject')
                    ->label(__('admin.letter_templates.fields.subject'))
                    ->required()
                    ->columnSpanFull(),

                Textarea::make('body')
                    ->label(__('admin.letter_templates.fields.body'))
                    ->required()
                    ->rows(16)
                    ->helperText(__('admin.letter_templates.body_helper'))
                    ->columnSpanFull(),

                Section::make(__('admin.letter_templates.placeholders.title'))
                    ->description(__('admin.letter_templates.placeholders.description'))
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        KeyValueEntry::make('placeholders')
                            ->key('placeholders')
                            ->hiddenLabel()
                            ->keyLabel(__('admin.letter_templates.placeholders.key'))
                            ->valueLabel(__('admin.letter_templates.placeholders.value'))
                            ->state(collect(__('admin.letter_templates.placeholders.list'))
                                ->mapWithKeys(fn (string $meaning, string $name) => ['{{ ' . $name . ' }}' => $meaning])
                                ->all()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('key')
                    ->label(__('admin.letter_templates.fields.key'))
                    ->badge()
                    ->formatStateUsing(fn (LetterEnum $state) => $state->label()),

                TextColumn::make('subject')
                    ->label(__('admin.letter_templates.fields.subject')),

                TextColumn::make('sender')
                    ->label(__('admin.letter_templates.fields.sender'))
                    ->placeholder(config('mail.from.address')),

                TextColumn::make('updated_at')
                    ->label(__('admin.letter_templates.fields.updated_at'))
                    ->since()
                    ->dateTimeTooltip('d.m.Y H:i:s'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->modalWidth('3xl'),
            ], position: RecordActionsPosition::BeforeColumns)
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLetterTemplates::route('/'),
        ];
    }
}
