<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\Page as PageModel;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ManageSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.manage-settings';

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin.settings.title');
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_language' => Setting::get('site_language', 'pl'),
            'homepage_id' => Setting::get('homepage_id'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('admin.settings.section_regionalization'))
                    ->schema([
                        Select::make('site_language')
                            ->label(__('admin.settings.field_language'))
                            ->options([
                                'pl' => 'Polski (PL)',
                                'en' => 'English (EN)',
                            ])
                            ->native(false)
                            ->required(),

                        Select::make('homepage_id')
                            ->label(__('admin.settings.field_homepage'))
                            ->options(PageModel::query()->pluck('title', 'id'))
                            ->searchable()
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.settings.save_button'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('site_language', $data['site_language']);
        Setting::set('homepage_id', $data['homepage_id']);

        Notification::make()
            ->title(__('admin.settings.notification_success'))
            ->success()
            ->send();

        $this->redirect(route('filament.admin.pages.manage-settings'));
    }
}