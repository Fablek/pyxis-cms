<?php

namespace App\Filament\CustomFields\FieldTypes;

use App\Filament\CustomFields\FieldDefinitionForm;
use App\Filament\CustomFields\FieldTypeRegistry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Icons\Heroicon;

class GroupField extends FieldType
{
    public function key(): string
    {
        return 'group';
    }

    public function label(): string
    {
        return __('admin.custom_fields.types.group');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedRectangleGroup;
    }

    public function hasSubFields(): bool
    {
        return true;
    }

    public function supportsRequired(): bool
    {
        return false;
    }

    public function generalSettings(int $depth): array
    {
        return [
            FieldDefinitionForm::make('sub_fields', $depth + 1)
                ->label(__('admin.custom_fields.definition.sub_fields'))
                ->columnSpanFull(),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        return Fieldset::make($config['label'] ?? $config['name'])
            ->statePath($config['name'])
            ->columns(['default' => 1, 'lg' => 12])
            ->schema(app(FieldTypeRegistry::class)->formComponents($config['sub_fields'] ?? []));
    }
}
