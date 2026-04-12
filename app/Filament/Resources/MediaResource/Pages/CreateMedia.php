<?php

namespace App\Filament\Resources\MediaResource\Pages;

use Awcodes\Curator\Resources\Media\Pages\CreateMedia as BaseCreateMedia;
use Filament\Actions;

class CreateMedia extends BaseCreateMedia
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Wymuszamy na sztywno, żeby sprawdzić czy to przejdzie do bazy
        $data['directory'] = 'media'; 
        
        // Ustawiamy brakujące pola, które w bazie są NOT NULL
        $data['disk'] = $data['disk'] ?? 'public';
        $data['visibility'] = $data['visibility'] ?? 'public';

        return $data;
    }

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label(__('admin.pages.actions.create') ?? 'Utwórz')
                ->color('primary')
                ->action('create'),

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