<?php

namespace App\Filament\Resources\FieldGroups\Pages;

use App\Filament\Resources\FieldGroups\Actions\PreviewFieldsAction;
use App\Filament\Resources\FieldGroups\FieldGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFieldGroup extends CreateRecord
{
    protected static string $resource = FieldGroupResource::class;

    public function getHeaderActions(): array
    {
        return [
            PreviewFieldsAction::make(),

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
