<?php

namespace App\Filament\CustomFields\FieldTypes;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class GalleryField extends FieldType
{
    public function key(): string
    {
        return 'gallery';
    }

    public function label(): string
    {
        return 'Galeria';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedSquares2x2;
    }

    public function validationSettings(): array
    {
        return [
            TextInput::make('max_items')
                ->label('Maksymalna liczba obrazów')
                ->numeric()
                ->minValue(1),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        $picker = CuratorPicker::make($config['name'])->multiple();

        if (filled($config['max_items'] ?? null)) {
            $picker->maxItems((int) $config['max_items']);
        }

        return $picker;
    }
}
