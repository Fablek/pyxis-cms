<?php

namespace App\Filament\CustomFields\FieldTypes;

use App\Models\Media;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class GalleryField extends FieldType
{
    public function key(): string
    {
        return 'gallery';
    }

    public function label(): string
    {
        return __('admin.custom_fields.types.gallery');
    }

    public function icon(): Heroicon
    {
        return Heroicon::OutlinedSquares2x2;
    }

    public function validationSettings(): array
    {
        return [
            TextInput::make('max_items')
                ->label(__('admin.custom_fields.settings.max_images'))
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

    public function toApi(mixed $value, array $config): mixed
    {
        $ids = array_values(array_filter((array) $value, fn (mixed $id): bool => is_string($id) && Str::isUuid($id)));
        $media = Media::query()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)
            ->map(fn (string $id): ?array => ImageField::mediaToApi($media->get($id)))
            ->filter()
            ->values()
            ->all();
    }
}
