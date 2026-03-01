<?php

namespace App\Filament\Pages;

use App\Models\Setting;
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
            'site_language' => Setting::where('key', 'site_language')->value('value') ?? 'pl',
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

        Setting::updateOrCreate(
            ['key' => 'site_language'],
            ['value' => $data['site_language']]
        );

        Notification::make()
            ->title(__('admin.settings.notification_success'))
            ->success()
            ->send();

        $this->redirect(route('filament.admin.pages.manage-settings'));
    }
}