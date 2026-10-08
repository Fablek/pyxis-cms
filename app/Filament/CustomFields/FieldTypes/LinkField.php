<?php

namespace App\Filament\CustomFields\FieldTypes;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Icons\Heroicon;

class LinkField extends FieldType
{
    public function key(): string
    {
        return 'link';
    }

    public function label(): string
    {
        return 'Link';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedLink;
    }

    protected function makeFormComponent(array $config): Component
    {
        $isRequired = (bool) ($config['required'] ?? false);

        return Fieldset::make($config['label'] ?? $config['name'])
            ->statePath($config['name'])
            ->columns(['default' => 1, 'md' => 2])
            ->schema([
                TextInput::make('url')
                    ->label('Adres URL')
                    ->placeholder('https:// lub /sciezka')
                    ->required($isRequired),

                TextInput::make('title')
                    ->label('Tekst linku'),

                Toggle::make('new_tab')
                    ->label('Otwórz w nowej karcie'),
            ]);
    }
}
