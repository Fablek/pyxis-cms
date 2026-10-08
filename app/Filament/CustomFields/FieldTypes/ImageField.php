<?php

namespace App\Filament\CustomFields\FieldTypes;

use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class ImageField extends FieldType
{
    public function key(): string
    {
        return 'image';
    }

    public function label(): string
    {
        return __('admin.custom_fields.types.image');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedPhoto;
    }

    protected function makeFormComponent(array $config): Component
    {
        return CuratorPicker::make($config['name']);
    }
}
