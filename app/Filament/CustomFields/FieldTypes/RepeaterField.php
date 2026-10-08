<?php

namespace App\Filament\CustomFields\FieldTypes;

use App\Filament\CustomFields\FieldDefinitionForm;
use App\Filament\CustomFields\FieldTypeRegistry;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class RepeaterField extends FieldType
{
    public function key(): string
    {
        return 'repeater';
    }

    public function label(): string
    {
        return 'Repeater (lista)';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedQueueList;
    }

    public function hasSubFields(): bool
    {
        return true;
    }

    public function generalSettings(int $depth): array
    {
        return [
            FieldDefinitionForm::make('sub_fields', $depth + 1)
                ->label('Pola podrzędne')
                ->columnSpanFull(),
        ];
    }

    public function validationSettings(): array
    {
        return [
            TextInput::make('min_items')
                ->label('Minimalna liczba elementów')
                ->numeric()
                ->minValue(0),

            TextInput::make('max_items')
                ->label('Maksymalna liczba elementów')
                ->numeric()
                ->minValue(1),
        ];
    }

    public function presentationSettings(): array
    {
        return [
            TextInput::make('button_label')
                ->label('Tekst przycisku dodawania')
                ->placeholder('Dodaj element'),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        return Repeater::make($config['name'])
            ->schema(app(FieldTypeRegistry::class)->formComponents($config['sub_fields'] ?? []))
            ->columns(['default' => 1, 'lg' => 12])
            ->minItems(filled($config['min_items'] ?? null) ? (int) $config['min_items'] : null)
            ->maxItems(filled($config['max_items'] ?? null) ? (int) $config['max_items'] : null)
            ->addActionLabel(filled($config['button_label'] ?? null) ? $config['button_label'] : 'Dodaj element')
            ->collapsible();
    }
}
