<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Http;
use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Models\Page;
use App\Models\Setting;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Builder as CompBuilder;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

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
                            ->hidden(function (Forms\Get $get, $record) {
                                if (!$record) return false;
                                return (string)$record->id === (string)Setting::get('homepage_id');
                            })
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->prefix(function (Forms\Get $get) {
                                $parentId = $get('parent_id');
                                if ($parentId) {
                                    // Download the parent servant from the database
                                    $parent = Page::find($parentId);
                                    
                                    if ($parent) {
                                        return rtrim($parent->full_url, '/') . '/';
                                    }
                                }
                                return '/';
                            })
                            // Prevent the user from entering slashes,
                            // because the prefix already adds them automatically
                            ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state))),

                        Placeholder::make('slug_placeholder')
                            ->label(__('admin.pages.fields.slug'))
                            ->content('/')
                            ->visible(function (Forms\Get $get, $record) {
                                if (!$record) return false;
                                return (string)$record->id === (string)Setting::get('homepage_id');
                            }),

                        CompBuilder::make('content_draft')
                            ->label(__('admin.pages.fields.content_draft'))
                            ->blocks([
                                // Blocks here (acf)
                            ])
                            ->columnSpanFull(),
                        
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
                                
                                Select::make('visibility')
                                    ->label(__('admin.pages.fields.visibility'))
                                    ->options([
                                        'public' => __('admin.pages.visibility.public'),
                                        'private' => __('admin.pages.visibility.private'),
                                        'password' => __('admin.pages.visibility.password'),
                                    ])
                                    ->default('public')
                                    ->native(false)
                                    ->required()
                                    ->live(),

                                DateTimePicker::make('published_at')
                                    ->label(__('admin.pages.fields.published_at'))
                                    ->default(now()) // Default now
                                    ->native(false),

                                TextInput::make('password')
                                    ->label(__('admin.pages.fields.password'))
                                    ->password()
                                    ->revealable()
                                    ->requiredIf('visibility', 'password')
                                    ->visible(fn ($get) => $get('visibility') === 'password'),

                                Actions::make([
                                    // Button preview
                                    Action::make('preview')
                                        ->label(__('admin.pages.actions.preview'))
                                        ->color('gray')
                                        ->icon('heroicon-o-eye')
                                        ->url(fn ($record) => $record->getPreviewUrl())
                                        ->openUrlInNewTab(),

                                    Action::make('publish')
                                        ->label(__('admin.pages.actions.publish'))
                                        ->color('success')
                                        ->icon('heroicon-o-rocket-launch')
                                        ->requiresConfirmation()
                                        ->modalHeading(__('admin.pages.modals.publish_confirm'))
                                        ->hidden(fn ($operation, $record) => $operation === 'create' || $record === null)
                                        ->action(function ($record, $livewire) {
                                            // First save changes
                                            $livewire->save();

                                            // 2. Then publish
                                            $record->update([
                                                'content' => $record->content_draft,
                                            ]);

                                            // Send signal to frontend
                                            try {
                                                $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
                                                $revalidateToken = config('app.revalidate_token', 'super-secret-token');

                                                // Send request to refresh a specific track
                                                Http::post("{$frontendUrl}/api/revalidate", [
                                                    'secret' => $revalidateToken,
                                                    'path' => $record->full_url === '/' ? '/' : $record->full_url,
                                                ]);
                                            } catch (\Exception $e) {
                                                // Log error but not stop publishing
                                                \Log::error("Revalidation error: " . $e->getMessage());
                                            }

                                            Notification::make()
                                                ->title(__('admin.pages.notifications.published'))
                                                ->success()
                                                ->send();
                                        }),
        
                                    // Button save
                                    Action::make('save')
                                        ->label(__('admin.pages.actions.save'))
                                        ->color('primary')
                                        ->submit('save'),

                                    // Button delete
                                    Action::make('delete')
                                        ->label(__('admin.pages.actions.delete'))
                                        ->color('danger')
                                        ->icon('heroicon-o-trash')
                                        ->requiresConfirmation() // This will trigger a modal asking for confirmation
                                        ->modalHeading(__('admin.pages.modals.delete_confirm'))
                                        ->hidden(fn ($operation) => $operation === 'create') // Hide while creating
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
                    ->formatStateUsing(function (string $state, Page $record): string {
                        if ($state === 'published' && $record->published_at > now()) {
                            return __('admin.pages.status.scheduled');
                        }
                        return __("admin.pages.status.{$state}");
                    })
                    ->color(function (string $state, Page $record): string {
                        if ($state === 'published' && $record->published_at > now()) {
                            return 'info';
                        }
                        return match ($state) {
                            'published' => 'success',
                            'draft' => 'warning',
                            default => 'gray',
                        };
                    }),
                
                TextColumn::make('visibility')
                    ->label(__('admin.pages.fields.visibility'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("admin.pages.visibility.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'public' => 'success',
                        'private' => 'gray',
                        'password' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'public' => 'heroicon-o-globe-alt',
                        'private' => 'heroicon-o-lock-closed',
                        'password' => 'heroicon-o-key',
                        default => 'heroicon-o-globe-alt',
                    }),

                TextColumn::make('published_at')
                    ->label(__('admin.pages.fields.published_at'))
                    ->dateTime('d.m.Y')
                    ->sortable(),

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
