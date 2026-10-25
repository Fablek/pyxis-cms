<?php

namespace App\Providers;

use App\Plugins\HookManager;
use App\Plugins\PluginManager;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(HookManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->make(PluginManager::class)->loadActive();
    }
}
