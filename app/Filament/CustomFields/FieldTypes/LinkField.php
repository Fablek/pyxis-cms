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
        return __('admin.custom_fields.types.link');
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
                    ->label(__('admin.custom_fields.link.url'))
                    ->placeholder(__('admin.custom_fields.link.url_placeholder'))
                    ->required($isRequired),

                TextInput::make('title')
                    ->label(__('admin.custom_fields.link.title')),

                Toggle::make('new_tab')
                    ->label(__('admin.custom_fields.link.new_tab')),
            ]);
    }
}
