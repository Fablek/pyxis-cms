<?php

namespace App\Filament\Resources\MediaResource\Pages;

use Awcodes\Curator\Resources\Media\Pages\CreateMedia as BaseCreateMedia;
use Filament\Actions;

class CreateMedia extends BaseCreateMedia
{
    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label(__('admin.pages.actions.create') ?? 'Utwórz')
                ->color('primary')
                ->submit('create'),

            Actions\Action::make('cancel')
                ->label(__('admin.pages.actions.cancel') ?? 'Anuluj')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    public function getFormActions(): array
    {
        return [];
    }
}