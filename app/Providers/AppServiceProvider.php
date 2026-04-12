<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Awcodes\Curator\Resources\Media\Pages\CreateMedia::class,
            \App\Filament\Resources\MediaResource\Pages\CreateMedia::class
        );

        $this->app->bind(
            \Awcodes\Curator\Resources\Media\Pages\EditMedia::class,
            \App\Filament\Resources\MediaResource\Pages\EditMedia::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
