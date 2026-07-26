<?php

namespace App\Filament\Resources\FieldGroups\Pages;

use App\Filament\Resources\FieldGroups\FieldGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFieldGroup extends CreateRecord
{
    protected static string $resource = FieldGroupResource::class;

    public function getHeaderActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->formId('form')
                ->color('primary')
                ->keyBindings(['mod+s']),

            $this->getCreateAnotherFormAction(),

            $this->getCancelFormAction(),
        ];
    }

    public function getFormActions(): array
    {
        return [];
    }
}
