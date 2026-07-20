<?php

namespace App\Filament\Resources\FieldGroups\Pages;

use App\Filament\Resources\FieldGroups\FieldGroupResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateFieldGroup extends CreateRecord
{
    protected static string $resource = FieldGroupResource::class;

    public function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
                ->color('primary')
                ->action('create')
                ->keyBindings(['mod+s']),

            Action::make('createAnother')
                ->label(__('filament-panels::resources/pages/create-record.form.actions.create_another.label'))
                ->color('gray')
                ->action('createAnother')
                ->keyBindings(['mod+shift+s']),

            Action::make('cancel')
                ->label(__('filament-panels::resources/pages/create-record.form.actions.cancel.label'))
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }

    public function getFormActions(): array
    {
        return [];
    }
}
