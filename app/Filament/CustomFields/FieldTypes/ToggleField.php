<?php

namespace App\Filament\CustomFields\FieldTypes;

use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class ToggleField extends FieldType
{
    public function key(): string
    {
        return 'toggle';
    }

    public function label(): string
    {
        return 'Przełącznik';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedCheckCircle;
    }

    public function supportsRequired(): bool
    {
        return false;
    }

    public function generalSettings(int $depth): array
    {
        return [
            Toggle::make('default_value')
                ->label('Domyślnie włączony'),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        return Toggle::make($config['name'])
            ->default((bool) ($config['default_value'] ?? false));
    }
}
