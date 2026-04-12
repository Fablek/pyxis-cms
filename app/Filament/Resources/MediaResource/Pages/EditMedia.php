<?php

namespace App\Filament\Resources\MediaResource\Pages;

use Awcodes\Curator\Resources\Media\Pages\EditMedia as BaseEditMedia;
use Filament\Actions;

class EditMedia extends BaseEditMedia
{
    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label(__('admin.pages.actions.save') ?? 'Zapisz')
                ->color('primary')
                ->submit('save'),

            Actions\Action::make('cancel')
                ->label(__('admin.pages.actions.cancel') ?? 'Anuluj')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),

            Actions\DeleteAction::make(),
        ];
    }

    public function getFormActions(): array
    {
        return [];
    }
}