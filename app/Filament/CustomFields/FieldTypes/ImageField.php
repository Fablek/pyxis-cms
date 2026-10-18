<?php

namespace App\Filament\CustomFields\FieldTypes;

use App\Models\Media;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

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

    public function toApi(mixed $value, array $config): mixed
    {
        return is_string($value) && Str::isUuid($value)
            ? self::mediaToApi(Media::find($value))
            : null;
    }

    /**
     * @return array{id: string, url: ?string, alt: ?string, title: ?string, width: ?int, height: ?int}|null
     */
    public static function mediaToApi(?Media $media): ?array
    {
        if ($media === null) {
            return null;
        }

        return [
            'id' => $media->id,
            'url' => $media->url,
            'alt' => $media->alt,
            'title' => $media->title,
            'width' => $media->width,
            'height' => $media->height,
        ];
    }
}
