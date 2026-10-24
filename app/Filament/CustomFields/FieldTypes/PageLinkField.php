<?php

namespace App\Filament\CustomFields\FieldTypes;

use App\Models\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class PageLinkField extends FieldType
{
    public function key(): string
    {
        return 'page';
    }

    public function label(): string
    {
        return __('admin.custom_fields.types.page');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedDocumentDuplicate;
    }

    public function generalSettings(int $depth): array
    {
        return [
            Toggle::make('multiple')
                ->label(__('admin.custom_fields.settings.multiple_pages')),
        ];
    }

    protected function makeFormComponent(array $config): Component
    {
        return Select::make($config['name'])
            ->options(fn (): array => Page::query()->orderBy('title')->pluck('title', 'id')->all())
            ->searchable()
            ->multiple((bool) ($config['multiple'] ?? false));
    }

    public function toApi(mixed $value, array $config): mixed
    {
        $ids = array_values(array_filter((array) $value, fn (mixed $id): bool => is_string($id) && Str::isUuid($id)));
        $pages = Page::query()->whereIn('id', $ids)->get()->keyBy('id');

        $linked = collect($ids)
            ->map(fn (string $id): ?Page => $pages->get($id))
            ->filter(fn (?Page $page): bool => $page?->isLive() ?? false)
            ->map(fn (Page $page): array => [
                'id' => $page->id,
                'title' => $page->title,
                'full_url' => $page->full_url,
            ])
            ->values();

        return ($config['multiple'] ?? false) ? $linked->all() : $linked->first();
    }
}
