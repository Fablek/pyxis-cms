<?php

namespace App\Filament\Resources\FieldGroups\Pages;

use App\Filament\Resources\FieldGroups\FieldGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFieldGroups extends ListRecords
{
    protected static string $resource = FieldGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
