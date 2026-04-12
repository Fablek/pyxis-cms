<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('create')
                ->label(__('admin.pages.actions.create') ?? 'Utwórz')
                ->color('primary')
                ->action('create'),

            Actions\Action::make('createAnother')
                ->label(__('admin.pages.actions.create_another') ?? 'Utwórz i utwórz kolejny')
                ->color('gray')
                ->action('createAnother'),

            Actions\Action::make('cancel')
                ->label(__('admin.pages.actions.cancel') ?? 'Anuluj')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}