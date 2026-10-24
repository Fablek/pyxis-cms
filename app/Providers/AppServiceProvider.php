<?php

namespace App\Providers;

use App\Filament\CustomFields\FieldTypeRegistry;
use App\Filament\CustomFields\FieldTypes;
use Awcodes\Curator\Resources\Media\Pages\CreateMedia;
use Awcodes\Curator\Resources\Media\Pages\EditMedia;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            CreateMedia::class,
            \App\Filament\Resources\MediaResource\Pages\CreateMedia::class
        );

        $this->app->bind(
            EditMedia::class,
            \App\Filament\Resources\MediaResource\Pages\EditMedia::class
        );

        $this->app->singleton(FieldTypeRegistry::class, fn (): FieldTypeRegistry => new FieldTypeRegistry([
            new FieldTypes\TextField,
            new FieldTypes\TextareaField,
            new FieldTypes\NumberField,
            new FieldTypes\WysiwygField,
            new FieldTypes\ImageField,
            new FieldTypes\GalleryField,
            new FieldTypes\SelectField,
            new FieldTypes\ToggleField,
            new FieldTypes\LinkField,
            new FieldTypes\PageLinkField,
            new FieldTypes\RepeaterField,
            new FieldTypes\GroupField,
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
