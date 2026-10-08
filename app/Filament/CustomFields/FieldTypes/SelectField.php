<?php

namespace App\Filament\CustomFields\FieldTypes;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class SelectField extends FieldType
{
    public function key(): string
    {
        return 'select';
    }

    public function label(): string
    {
        return __('admin.custom_fields.types.select');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedChevronUpDown;
    }

    public function generalSettings(int $depth): array
    {
        return [
            Repeater::make('options')
                ->label(__('admin.custom_fields.settings.options'))
                ->table([
                    TableColumn::make(__('admin.custom_fields.settings.option_value'))->markAsRequired(),
                    TableColumn::make(__('admin.custom_fields.settings.option_label'))->markAsRequired(),
                ])
                ->compact()
                ->schema([
                    TextInput::make('value')->required(),
                    TextInput::make('label')->required(),
                ])
                ->addActionLabel(__('admin.custom_fields.settings.add_option'))
                ->reorderable()
                ->defaultItems(1)
                ->columnSpanFull(),

            Toggle::make('multiple')
                ->label(__('admin.custom_fields.settings.multiple')),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        return Select::make($config['name'])
            ->options(collect($config['options'] ?? [])->pluck('label', 'value')->all())
            ->multiple((bool) ($config['multiple'] ?? false));
    }
}
