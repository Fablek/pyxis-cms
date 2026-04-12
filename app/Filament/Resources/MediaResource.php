<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaResource\Pages;
use Awcodes\Curator\Resources\Media\MediaResource as BaseMediaResource;

class MediaResource extends BaseMediaResource
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-photo';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getModelLabel(): string
    {
        return __('admin.media.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.media.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.media.nav_label');
    }

    public static function getPages(): array
    {
        return [
            'index' => \Awcodes\Curator\Resources\Media\Pages\ListMedia::route('/'),
            'create' => \App\Filament\Resources\MediaResource\Pages\CreateMedia::route('/create'),
            'edit' => \App\Filament\Resources\MediaResource\Pages\EditMedia::route('/{record}/edit'),
        ];
    }
}