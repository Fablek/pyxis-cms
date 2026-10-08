<?php

namespace App\Filament\CustomFields\FieldTypes;

use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class WysiwygField extends FieldType
{
    public function key(): string
    {
        return 'wysiwyg';
    }

    public function label(): string
    {
        return 'Edytor WYSIWYG';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedDocumentText;
    }

    protected function makeFormComponent(array $config): Component
    {
        return RichEditor::make($config['name']);
    }
}
