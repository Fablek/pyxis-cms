<?php

namespace App\Filament\Resources\Plugins\Tables;

use App\Models\Plugin;
use App\Plugins\PluginManager;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PluginsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.plugins.fields.name'))
                    ->description(fn (Plugin $record): ?string => $record->description)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('version')
                    ->label(__('admin.plugins.fields.version'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('author')
                    ->label(__('admin.plugins.fields.author'))
                    ->placeholder('—'),
                IconColumn::make('is_active')
                    ->label(__('admin.plugins.fields.is_active'))
                    ->boolean(),
                TextColumn::make('error')
                    ->label(__('admin.plugins.fields.error'))
                    ->color('danger')
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('activate')
                    ->label(__('admin.plugins.actions.activate'))
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('success')
                    ->hidden(fn (Plugin $record): bool => $record->is_active)
                    ->action(function (Plugin $record): void {
                        if (app(PluginManager::class)->activate($record)) {
                            Notification::make()->success()->title(__('admin.plugins.notifications.activated'))->send();

                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title(__('admin.plugins.notifications.activation_failed'))
                            ->body($record->fresh()->error)
                            ->send();
                    }),
                Action::make('deactivate')
                    ->label(__('admin.plugins.actions.deactivate'))
                    ->icon(Heroicon::OutlinedPause)
                    ->color('gray')
                    ->visible(fn (Plugin $record): bool => $record->is_active)
                    ->action(function (Plugin $record): void {
                        app(PluginManager::class)->deactivate($record);

                        Notification::make()->success()->title(__('admin.plugins.notifications.deactivated'))->send();
                    }),
                DeleteAction::make()
                    ->hidden(fn (Plugin $record): bool => $record->is_active)
                    ->schema([
                        Checkbox::make('delete_data')
                            ->label(__('admin.plugins.fields.delete_data')),
                    ])
                    ->using(fn (Plugin $record, array $data) => app(PluginManager::class)->uninstall($record, $data['delete_data'] ?? false)),
            ]);
    }
}
