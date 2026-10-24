<?php

namespace App\Filament\CustomFields\FieldTypes;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class NumberField extends FieldType
{
    public function key(): string
    {
        return 'number';
    }

    public function label(): string
    {
        return __('admin.custom_fields.types.number');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedHashtag;
    }

    public function validationSettings(): array
    {
        return [
            TextInput::make('min')
                ->label(__('admin.custom_fields.settings.min'))
                ->numeric(),

            TextInput::make('max')
                ->label(__('admin.custom_fields.settings.max'))
                ->numeric(),

            TextInput::make('step')
                ->label(__('admin.custom_fields.settings.step'))
                ->numeric(),
        ];
    }

    public function presentationSettings(): array
    {
        return [
            TextInput::make('suffix')
                ->label(__('admin.custom_fields.settings.suffix'))
                ->placeholder(__('admin.custom_fields.settings.suffix_placeholder')),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        return TextInput::make($config['name'])
            ->numeric()
            ->minValue(filled($config['min'] ?? null) ? (float) $config['min'] : null)
            ->maxValue(filled($config['max'] ?? null) ? (float) $config['max'] : null)
            ->step(filled($config['step'] ?? null) ? (float) $config['step'] : null)
            ->suffix($config['suffix'] ?? null);
    }
}
