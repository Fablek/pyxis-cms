<?php

namespace App\Filament\Resources;

use App\Enums\PageStatus;
use App\Enums\PageVisibility;
use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use App\Models\Setting;
use App\Services\PageService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Builder as CompBuilder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'sm' => 1,
                'lg' => 3, 
            ])
            ->components([
                
                // Left Column (2/3)
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label(__('admin.pages.fields.title'))
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug($state))),
                    
                        TextInput::make('slug')
                            ->label(__('admin.pages.fields.slug'))
                            ->hidden(function (Get $get, $record) {
                                if (!$record) return false;
                                return (string)$record->id === (string)Setting::get('homepage_id');
                            })
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->prefix(function (Get $get) {
                                $parentId = $get('parent_id');
                                if (!$parentId) return '/';
                                $parent = once(fn () => Page::with('ancestors')->find($parentId));
                                return $parent ? rtrim($parent->full_url, '/') . '/' : '/';
                            })
                            ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug($state))),

                        Placeholder::make('slug_placeholder')
                            ->label(__('admin.pages.fields.slug'))
                            ->content('/')
                            ->visible(function (Get $get, $record) {
                                if (!$record) return false;
                                return (string)$record->id === (string)Setting::get('homepage_id');
                            }),

                        CompBuilder::make('content_draft')
                            ->label(__('admin.pages.fields.content_draft'))
                            ->blocks([])
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpan([
                        'sm' => 1,
                        'lg' => 2,
                    ]),

                // Right Column (sidebar - 1/3)
                Group::make([
                    Section::make(__('admin.pages.sections.publish'))
                        ->schema([
                            Select::make('status')
                                ->label(__('admin.pages.fields.status'))
                                ->options([
                                    PageStatus::DRAFT->value => __('admin.pages.status.draft'),
                                    PageStatus::PUBLISHED->value => __('admin.pages.status.published'),
                                ])
                                ->default(PageStatus::DRAFT)
                                ->native(false)
                                ->required(),
                        
                            Select::make('visibility')
                                ->label(__('admin.pages.fields.visibility'))
                                ->options([
                                    PageVisibility::PUBLIC->value => __('admin.pages.visibility.public'),
                                    PageVisibility::PRIVATE->value => __('admin.pages.visibility.private'),
                                    PageVisibility::PASSWORD->value => __('admin.pages.visibility.password'),
                                ])
                                ->default(PageVisibility::PUBLIC)
                                ->native(false)
                                ->required()
                                ->live(),

                            DateTimePicker::make('published_at')
                                ->label(__('admin.pages.fields.published_at'))
                                ->default(now())
                                ->native(false),

                            TextInput::make('password')
                                ->label(__('admin.pages.fields.password'))
                                ->password()
                                ->revealable()
                                ->requiredIf('visibility', PageVisibility::PASSWORD->value)
                                ->visible(fn (Get $get) => $get('visibility') === PageVisibility::PASSWORD->value),

                            Actions::make([
                                Action::make('preview')
                                    ->label(__('admin.pages.actions.preview'))
                                    ->color('gray')
                                    ->icon('heroicon-o-eye')
                                    ->url(fn ($record) => app(PageService::class)->getPreviewUrl($record))
                                    ->visible(fn ($record) => $record !== null)
                                    ->openUrlInNewTab(),

                                Action::make('publish')
                                    ->label(__('admin.pages.actions.publish'))
                                    ->color('success')
                                    ->icon('heroicon-o-rocket-launch')
                                    ->requiresConfirmation()
                                    ->action(function ($livewire) {
                                        if (method_exists($livewire, 'save')) {
                                            $livewire->save();
                                        } else {
                                            $livewire->create();
                                        }

                                        $record = $livewire->getRecord();

                                        $record->update([
                                            'content' => $record->content_draft,
                                            'status' => PageStatus::PUBLISHED,
                                        ]);
                                    
                                        try {
                                            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
                                            $revalidateToken = config('app.revalidate_token', 'super-secret-token');
                                            
                                            \Illuminate\Support\Facades\Http::post("{$frontendUrl}/api/revalidate", [
                                                'secret' => $revalidateToken,
                                                'path' => $record->full_url === '/' ? '/' : $record->full_url,
                                            ]);
                                        } catch (\Exception $e) {
                                            \Log::error("Revalidation error: " . $e->getMessage());
                                        }

                                        \Filament\Notifications\Notification::make()
                                            ->title(__('admin.pages.notifications.published'))
                                            ->success()
                                            ->send();
                                    }),

                                    Action::make('save')
                                        ->label(__('admin.pages.actions.draft'))
                                        ->color('primary')
                                        ->submit('save'),

                                    Action::make('delete')
                                        ->label(__('admin.pages.actions.delete'))
                                        ->color('danger')
                                        ->icon('heroicon-o-trash')
                                        ->requiresConfirmation()
                                        ->hidden(fn ($operation) => $operation === 'create')
                                        ->action(function ($record, $livewire) {
                                            $record->delete();
                                            return redirect($livewire->getResource()::getUrl('index'));
                                        }),
                            ]),
                        ])
                        ->columns(1),
                    
                    Section::make(__('admin.pages.sections.attributes'))
                        ->schema([
                            Select::make('parent_id')
                                ->label(__('admin.pages.fields.parent'))
                                ->relationship('parent', 'title')
                                ->searchable()
                                ->placeholder(__('admin.pages.placeholders.none_root'))
                                ->live(),
                        ])
                        ->columns(1),
                ])
                ->columnSpan([
                    'sm' => 1,
                    'lg' => 1,
                ]),
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
                    ->formatStateUsing(function (PageStatus $state, Page $record): string {
                        if ($state === PageStatus::PUBLISHED && $record->published_at > now()) {
                            return __('admin.pages.status.scheduled');
                        }
                        return __("admin.pages.status.{$state->value}");
                    })
                    ->color(function (PageStatus $state, Page $record): string {
                        if ($state === PageStatus::PUBLISHED && $record->published_at > now()) {
                            return 'info';
                        }
                        return match ($state) {
                            PageStatus::PUBLISHED => 'success',
                            PageStatus::DRAFT => 'warning',
                            default => 'gray',
                        };
                    }),
                
                TextColumn::make('visibility')
                    ->label(__('admin.pages.fields.visibility'))
                    ->badge()
                    ->formatStateUsing(fn (PageVisibility $state): string => __("admin.pages.visibility.{$state->value}"))
                    ->color(fn (PageVisibility $state): string => match ($state) {
                        PageVisibility::PUBLIC => 'success',
                        PageVisibility::PRIVATE => 'gray',
                        PageVisibility::PASSWORD => 'info',
                    })
                    ->icon(fn (PageVisibility $state): string => match ($state) {
                        PageVisibility::PUBLIC => 'heroicon-o-globe-alt',
                        PageVisibility::PRIVATE => 'heroicon-o-lock-closed',
                        PageVisibility::PASSWORD => 'heroicon-o-key',
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
                        PageStatus::DRAFT->value => __('admin.pages.status.draft'),
                        PageStatus::PUBLISHED->value => __('admin.pages.status.published'),
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
        return [];
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