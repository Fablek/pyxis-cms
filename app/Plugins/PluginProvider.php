<?php

namespace App\Plugins;

use Filament\Contracts\Plugin as FilamentPlugin;
use Filament\Panel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base service provider for plugins, with helpers for their routes and the admin panel.
 */
abstract class PluginProvider extends ServiceProvider
{
    protected string $slug;

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * Returns a path inside the plugin directory.
     */
    protected function pluginPath(string $path = ''): string
    {
        return $this->app->make(PluginManager::class)->path($this->slug).($path === '' ? '' : '/'.ltrim($path, '/'));
    }

    /**
     * Registers the plugin's API routes under /api/plugins/{slug} with the api middleware group.
     */
    protected function loadPluginRoutes(string $path): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware('api')
            ->prefix("api/plugins/{$this->slug}")
            ->name("plugins.{$this->slug}.")
            ->group($path);
    }

    /**
     * Adds a Filament plugin (resources, pages, widgets) to the admin panel.
     */
    protected function registerAdminPlugin(FilamentPlugin $plugin): void
    {
        Hook::filter('admin.panel', fn (Panel $panel): Panel => $panel->plugin($plugin));
    }
}
