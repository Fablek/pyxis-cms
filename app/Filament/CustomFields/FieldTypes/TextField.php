<?php

namespace App\Filament\CustomFields\FieldTypes;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class TextField extends FieldType
{
    public function key(): string
    {
        return 'text';
    }

    public function label(): string
    {
        return __('admin.custom_fields.types.text');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedBars2;
    }

    public function generalSettings(int $depth): array
    {
        return [
            TextInput::make('default_value')
                ->label(__('admin.custom_fields.settings.default_value')),
        ];
    }

    public function validationSettings(): array
    {
        return [
            TextInput::make('max_length')
                ->label(__('admin.custom_fields.settings.max_length'))
                ->numeric()
                ->minValue(1),
        ];
    }

    public function presentationSettings(): array
    {
        return [
            TextInput::make('placeholder')
                ->label(__('admin.custom_fields.settings.placeholder')),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        return TextInput::make($config['name'])
            ->placeholder($config['placeholder'] ?? null)
            ->default($config['default_value'] ?? null)
            ->maxLength(filled($config['max_length'] ?? null) ? (int) $config['max_length'] : null);
    }
}
