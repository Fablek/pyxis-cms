<?php

namespace App\Filament\CustomFields\FieldTypes;

use App\Models\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class PageLinkField extends FieldType
{
    public function key(): string
    {
        return 'page';
    }

    public function label(): string
    {
        return 'Strona (relacja)';
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedDocumentDuplicate;
    }

    public function generalSettings(int $depth): array
    {
        return [
            Toggle::make('multiple')
                ->label('Wiele stron'),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        return Select::make($config['name'])
            ->options(fn (): array => Page::query()->orderBy('title')->pluck('title', 'id')->all())
            ->searchable()
            ->multiple((bool) ($config['multiple'] ?? false));
    }
}
