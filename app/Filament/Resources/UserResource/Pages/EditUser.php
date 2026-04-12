<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label(__('admin.pages.actions.save'))
                ->color('primary')
                ->submit('save'),

            Actions\Action::make('cancel')
                ->label(__('admin.pages.actions.cancel') ?? 'Anuluj')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
