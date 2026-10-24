<?php

namespace App\Filament\Resources\FieldGroups\Pages;

use App\Filament\Resources\FieldGroups\Actions\PreviewFieldsAction;
use App\Filament\Resources\FieldGroups\FieldGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFieldGroup extends EditRecord
{
    protected static string $resource = FieldGroupResource::class;

    public function getHeaderActions(): array
    {
        return [
            PreviewFieldsAction::make(),

            $this->getSaveFormAction()
                ->formId('form')
                ->color('primary')
                ->keyBindings(['mod+s']),

            $this->getCancelFormAction(),

            DeleteAction::make(),
        ];
    }

    public function getFormActions(): array
    {
        return [];
    }
}
