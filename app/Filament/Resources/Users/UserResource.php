<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Modules\User\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin panel users: the seeded ones are only edited, not created or deleted.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Russian labels: only the first word is capitalised.
    protected static bool $hasTitleCaseModelLabel = false;

    public static function getModelLabel(): string
    {
        return __('admin.users.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users.plural');
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
                TextInput::make('name')
                    ->label(__('admin.users.fields.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('admin.users.fields.email'))
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                // Hashed by the model's cast; left empty, the password is not changed.
                TextInput::make('password')
                    ->label(__('admin.users.fields.password'))
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(__('admin.users.password_helper'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.users.fields.name'))
                    ->searchable(),

                TextColumn::make('email')
                    ->label(__('admin.users.fields.email'))
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label(__('admin.users.fields.created_at'))
                    ->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
            ], position: RecordActionsPosition::BeforeColumns);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
