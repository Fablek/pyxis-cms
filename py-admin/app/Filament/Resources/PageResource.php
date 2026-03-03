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
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Actions;

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
                Split::make([

                    // Left column
                    Section::make([
                        TextInput::make('title')
                            ->label(__('admin.pages.fields.title'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),
                        
                        TextInput::make('slug')
                            ->label(__('admin.pages.fields.slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->prefix(function (Forms\Get $get) {
                                $parentId = $get('parent_id');
                                if ($parentId) {
                                    // Download the parent servant from the database
                                    $parent = \App\Models\Page::find($parentId);
                                    return $parent ? '/' . $parent->slug . '/' : '/';
                                }
                                return '/';
                            })
                            // Prevent the user from entering slashes,
                            // because the prefix already adds them automatically
                            ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),
                        
                        // Builder blocks here
                    ]),

                    // Right column
                    Group::make([

                        // Section (Public)
                        Section::make(__('admin.pages.sections.publish'))
                            ->schema([
                                Select::make('status')
                                    ->label(__('admin.pages.fields.status'))
                                    ->options([
                                        'draft' => __('admin.pages.status.draft'),
                                        'published' => __('admin.pages.status.published'),
                                    ])
                                    ->default('draft')
                                    ->native(false)
                                    ->required(),

                                Actions::make([
                                    // Button save
                                    Forms\Components\Actions\Action::make('save')
                                        ->label(__('admin.pages.actions.save'))
                                        ->color('primary')
                                        ->submit('save'),

                                    // Button delete
                                    Forms\Components\Actions\Action::make('delete')
                                        ->label(__('admin.pages.actions.delete'))
                                        ->color('danger')
                                        ->icon('heroicon-o-trash')
                                        ->requiresConfirmation() // This will trigger a modal asking for confirmation
                                        ->modalHeading(__('admin.pages.modals.delete_confirm'))
                                        ->hidden(fn ($operation) => $operation === 'create') // We hide while creating
                                        ->action(function ($record, $livewire) {
                                            $record->delete();
                                            // After deletion, we return to the list of pages
                                            return redirect($livewire->getResource()::getUrl('index'));
                                        }),
                                ])->alignEnd(),
                            ]),
                        
                        // Section: Atributes
                        Section::make(__('admin.pages.sections.attributes'))
                            ->schema([
                                Select::make('parent_id')
                                    ->label(__('admin.pages.fields.parent'))
                                    ->relationship('parent', 'title')
                                    ->searchable()
                                    ->placeholder(__('admin.pages.placeholders.none_root'))
                                    ->live(),
                            ]),

                    ])->grow(false), // This column will not expand (fixed width)
                ])->from('md')->columnSpanFull(), // The column layout is enabled from tablets (md) upwards
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.pages.fields.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('admin.pages.fields.slug'))
                    ->icon('heroicon-o-link')
                    ->color('gray'),

                TextColumn::make('status')
                    ->label(__('admin.pages.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("admin.pages.status.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('user.name')
                    ->label(__('admin.pages.fields.author'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => __('admin.pages.status.draft'),
                        'published' => __('admin.pages.status.published'),
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
