<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.users');
    }

    public static function getModelLabel(): string
    {
        return __('admin.users.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.users.sections.basic_data'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('admin.users.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label(__('admin.users.fields.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('role_id')
                            ->label(__('admin.users.fields.role'))
                            ->relationship('role', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => __('admin.roles.' . $record->slug))
                            ->required()
                            ->preload()
                            ->searchable(),
                    ])->columns(2),

                Forms\Components\Section::make(__('admin.users.sections.security'))
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label(__('admin.users.fields.password'))
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('admin.users.fields.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('admin.users.fields.email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.name')
                    ->label(__('admin.users.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (string $state, $record): string => 
                        __('admin.roles.' . $record->role->slug) // Tłumaczymy na podstawie sluga
                    )
                    ->color(fn (string $state, $record): string => match ($record->role->slug) {
                        'admin' => 'danger',
                        'editor' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.users.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}