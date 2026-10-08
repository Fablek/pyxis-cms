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
        return 'Liczba';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedHashtag;
    }

    public function validationSettings(): array
    {
        return [
            TextInput::make('min')
                ->label('Minimum')
                ->numeric(),

            TextInput::make('max')
                ->label('Maksimum')
                ->numeric(),

            TextInput::make('step')
                ->label('Krok')
                ->numeric(),
        ];
    }

    public function presentationSettings(): array
    {
        return [
            TextInput::make('suffix')
                ->label('Jednostka (sufiks)')
                ->placeholder('np. zł, %, px'),
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
