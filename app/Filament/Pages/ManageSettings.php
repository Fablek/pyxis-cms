<?php

namespace App\Filament\Pages;

use App\Models\Page as PageModel;
use App\Models\Setting;
use App\Services\AiSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ManageSettings extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.manage-settings';

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin.settings.title');
    }

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_language' => Setting::get('site_language', 'pl'),
            'homepage_id' => Setting::get('homepage_id'),
            'ai_enabled' => (bool) Setting::get('ai_enabled', false),
            'ai_provider' => Setting::get('ai_provider'),
            'ai_model' => Setting::get('ai_model'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.settings.sections.config'))
                    ->description(__('admin.settings.sections.config_desc'))
                    ->components([
                        Grid::make(2)
                            ->components([
                                Select::make('site_language')
                                    ->label(__('admin.settings.fields.language'))
                                    ->options([
                                        'pl' => 'Polski (PL)',
                                        'en' => 'English (EN)',
                                    ])
                                    ->native(false)
                                    ->required(),

                                Select::make('homepage_id')
                                    ->label(__('admin.settings.fields.homepage'))
                                    ->options(PageModel::query()->pluck('title', 'id'))
                                    ->placeholder(__('admin.settings.placeholders.select_page'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false),
                            ]),
                    ]),

                Section::make(__('admin.settings.sections.ai'))
                    ->description(__('admin.settings.sections.ai_desc'))
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->components([
                        Toggle::make('ai_enabled')
                            ->label(__('admin.settings.fields.ai_enabled'))
                            ->live(),

                        Grid::make(2)
                            ->components([
                                Select::make('ai_provider')
                                    ->label(__('admin.settings.fields.ai_provider'))
                                    ->options(AiSettings::PROVIDERS)
                                    ->native(false)
                                    ->required(fn (Get $get): bool => (bool) $get('ai_enabled')),

                                TextInput::make('ai_model')
                                    ->label(__('admin.settings.fields.ai_model'))
                                    ->placeholder(__('admin.settings.placeholders.ai_model'))
                                    ->maxLength(255),
                            ]),

                        TextInput::make('ai_api_key')
                            ->label(__('admin.settings.fields.ai_api_key'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required(fn (Get $get): bool => (bool) $get('ai_enabled') && ! app(AiSettings::class)->hasApiKey())
                            ->helperText(fn (): string => app(AiSettings::class)->hasApiKey()
                                ? __('admin.settings.helpers.ai_api_key_saved')
                                : __('admin.settings.helpers.ai_api_key')),
                    ]),

            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.settings.save_button'))
                ->color('primary')
                ->action('save'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::set('site_language', $state['site_language']);
        Setting::set('homepage_id', $state['homepage_id']);

        if (auth()->user()?->isAdmin()) {
            Setting::set('ai_enabled', $state['ai_enabled'] ?? false);
            Setting::set('ai_provider', $state['ai_provider'] ?? null);
            Setting::set('ai_model', $state['ai_model'] ?? null);

            if (filled($state['ai_api_key'] ?? null)) {
                app(AiSettings::class)->storeApiKey($state['ai_api_key']);
            }
        }

        Notification::make()
            ->title(__('admin.settings.notification_success'))
            ->success()
            ->send();
    }
}
