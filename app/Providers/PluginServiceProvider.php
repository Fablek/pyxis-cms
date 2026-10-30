<?php

namespace App\Providers;

use App\Plugins\HookManager;
use App\Plugins\PluginManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(HookManager::class);

        // Plugins load after every provider is registered but before any is booted,
        // so they can still extend the admin panel and routes (Filament builds both while booting).
        $this->app->booting(function (): void {
            // Eloquent receives its connection only when the database provider boots, which happens after this callback.
            Model::setConnectionResolver($this->app['db']);
            Model::setEventDispatcher($this->app['events']);

            $this->app->make(PluginManager::class)->loadActive();
        });
    }
}
