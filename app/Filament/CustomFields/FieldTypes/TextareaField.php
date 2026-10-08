<?php

namespace App\Filament\CustomFields\FieldTypes;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class TextareaField extends FieldType
{
    public function key(): string
    {
        return 'textarea';
    }

    public function label(): string
    {
        return __('admin.custom_fields.types.textarea');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedBars3BottomLeft;
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

            TextInput::make('rows')
                ->label(__('admin.custom_fields.settings.rows'))
                ->numeric()
                ->minValue(1)
                ->default(4),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        return Textarea::make($config['name'])
            ->placeholder($config['placeholder'] ?? null)
            ->rows((int) ($config['rows'] ?? 4))
            ->maxLength(filled($config['max_length'] ?? null) ? (int) $config['max_length'] : null);
    }
}
