<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\Page as PageModel;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;

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

        Notification::make()
            ->title(__('admin.settings.notification_success'))
            ->success()
            ->send();
    }
}