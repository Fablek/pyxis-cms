<?php

namespace App\Filament\Resources\Plugins\Pages;

use App\Filament\Resources\Plugins\PluginResource;
use App\Plugins\PluginInstallException;
use App\Plugins\PluginManager;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManagePlugins extends ManageRecords
{
    protected static string $resource = PluginResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label(__('admin.plugins.actions.upload'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->schema([
                    FileUpload::make('archive')
                        ->label(__('admin.plugins.fields.archive'))
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(2048)
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data, Action $action): void {
                    try {
                        $plugin = app(PluginManager::class)->install($data['archive']->getRealPath());
                    } catch (PluginInstallException $exception) {
                        Notification::make()
                            ->danger()
                            ->title(__('admin.plugins.notifications.install_failed'))
                            ->body($exception->getMessage())
                            ->send();

                        $action->halt();
                    }

                    Notification::make()
                        ->success()
                        ->title(__('admin.plugins.notifications.installed', ['name' => $plugin->name]))
                        ->send();
                }),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        app(PluginManager::class)->sync();
    }
}
