<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Set;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.pages');
    }

    public static function getModelLabel(): string
    {
        return __('admin.pages.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.pages.plural_label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('admin.pages.sections.general'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('admin.pages.fields.title'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label(__('admin.pages.fields.slug'))
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('status')
                            ->label(__('admin.pages.fields.status'))
                            ->options([
                                'draft' => __('admin.pages.status.draft'),
                                'published' => __('admin.pages.status.published'),
                            ])
                            ->default('draft')
                            ->native(false)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin.pages.fields.title'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label(__('admin.pages.fields.slug'))
                    ->icon('heroicon-o-link')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.pages.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("admin.pages.status.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('admin.pages.fields.author'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                    ]),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
