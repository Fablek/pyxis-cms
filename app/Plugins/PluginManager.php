<?php

namespace App\Plugins;

use App\Models\Plugin;
use Composer\Autoload\ClassLoader;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Discovers plugins in the plugins directory and keeps the plugins table in sync with it.
 */
class PluginManager
{
    public const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(protected Application $app) {}

    public function path(string $slug = ''): string
    {
        $base = rtrim((string) config('pyxis.plugins.path'), '/');

        return $slug === '' ? $base : "{$base}/{$slug}";
    }

    /**
     * Reads and validates the plugin.json manifest of a plugin directory.
     *
     * @return array{slug: string, name: string, version: string, description: ?string, author: ?string, autoload: array<string, string>, provider: ?string}|null
     */
    public function readManifest(string $directory): ?array
    {
        $file = $directory.'/plugin.json';

        if (! is_file($file)) {
            return null;
        }

        $manifest = json_decode((string) file_get_contents($file), true);

        if (! is_array($manifest)) {
            return null;
        }

        foreach (['name', 'slug', 'version'] as $key) {
            if (! is_string($manifest[$key] ?? null) || $manifest[$key] === '') {
                return null;
            }
        }

        if (! preg_match(self::SLUG_PATTERN, $manifest['slug'])) {
            return null;
        }

        $autoload = $manifest['autoload'] ?? [];

        if (! is_array($autoload) || ! $this->isValidAutoload($autoload)) {
            return null;
        }

        $provider = $manifest['provider'] ?? null;

        if ($provider !== null && (! is_string($provider) || $autoload === [])) {
            return null;
        }

        return [
            'slug' => $manifest['slug'],
            'name' => $manifest['name'],
            'version' => $manifest['version'],
            'description' => is_string($manifest['description'] ?? null) ? $manifest['description'] : null,
            'author' => is_string($manifest['author'] ?? null) ? $manifest['author'] : null,
            'autoload' => $autoload,
            'provider' => $provider,
        ];
    }

    /**
     * Registers new plugins (inactive), updates known ones and removes plugins whose directory is gone.
     *
     * @return Collection<int, Plugin>
     */
    public function sync(): Collection
    {
        $directories = is_dir($this->path()) ? File::directories($this->path()) : [];

        $manifests = collect($directories)
            ->mapWithKeys(function (string $directory): array {
                $manifest = $this->readManifest($directory);

                if ($manifest === null || $manifest['slug'] !== basename($directory)) {
                    Log::warning('Skipped a plugin with a missing or invalid plugin.json.', ['directory' => $directory]);

                    return [];
                }

                return [$manifest['slug'] => $manifest];
            });

        foreach ($manifests as $slug => $manifest) {
            Plugin::query()->updateOrCreate(['slug' => $slug], Arr::only($manifest, ['name', 'version', 'description', 'author']));
        }

        Plugin::query()->whereNotIn('slug', $manifests->keys())->delete();

        return Plugin::query()->orderBy('name')->get();
    }

    /**
     * Loads every active plugin. Does nothing when the database or the plugins table is not ready yet (e.g. before migrations).
     */
    public function loadActive(): void
    {
        try {
            $plugins = Plugin::query()->where('is_active', true)->get();
        } catch (QueryException) {
            return;
        }

        foreach ($plugins as $plugin) {
            $this->load($plugin);
        }
    }

    /**
     * Registers the plugin's autoloader, requires its plugin.php and boots its provider.
     * A plugin that fails is deactivated and its error is stored, so it cannot break the CMS.
     */
    public function load(Plugin $plugin): bool
    {
        $directory = $this->path($plugin->slug);

        try {
            $manifest = $this->readManifest($directory) ?? throw new RuntimeException('Invalid plugin.json file.');
            $file = $directory.'/plugin.php';

            if (! is_file($file) && $manifest['provider'] === null) {
                throw new RuntimeException('Missing plugin.php file.');
            }

            $this->registerAutoload($directory, $manifest['autoload']);

            if (is_file($file)) {
                (static function (string $file): void {
                    require_once $file;
                })($file);
            }

            if ($manifest['provider'] !== null) {
                $this->bootProvider($manifest['provider'], $plugin->slug);
            }
        } catch (Throwable $exception) {
            return $this->fail($plugin, $exception);
        }

        return true;
    }

    /**
     * Runs the plugin's migrations, activates it and loads it right away,
     * so a broken plugin is caught at activation instead of on the next request.
     */
    public function activate(Plugin $plugin): bool
    {
        try {
            $this->migrate($plugin);
        } catch (Throwable $exception) {
            return $this->fail($plugin, $exception);
        }

        $plugin->update(['is_active' => true, 'error' => null]);

        if (! $this->load($plugin)) {
            return false;
        }

        Hook::doAction('plugin.activated', $plugin);

        return true;
    }

    /**
     * Deactivates a plugin. The action runs first, while the plugin's own callbacks are still registered.
     */
    public function deactivate(Plugin $plugin): void
    {
        Hook::doAction('plugin.deactivated', $plugin);

        $plugin->update(['is_active' => false]);
    }

    /**
     * Installs a plugin from a ZIP archive with plugin.json at its root or inside a single top-level directory.
     *
     * @throws PluginInstallException
     */
    public function install(string $archivePath): Plugin
    {
        $zip = new ZipArchive;

        if ($zip->open($archivePath) !== true) {
            throw new PluginInstallException(__('admin.plugins.errors.invalid_archive'));
        }

        $temporary = storage_path('framework/plugin-upload-'.Str::random(16));

        try {
            $this->assertSafeEntries($zip);
            $zip->extractTo($temporary);
            $zip->close();

            $root = $this->findRoot($temporary);
            $manifest = $root === null ? null : $this->readManifest($root);

            if ($manifest === null) {
                throw new PluginInstallException(__('admin.plugins.errors.invalid_manifest'));
            }

            if (is_dir($this->path($manifest['slug']))) {
                throw new PluginInstallException(__('admin.plugins.errors.already_installed', ['slug' => $manifest['slug']]));
            }

            File::ensureDirectoryExists($this->path());
            File::moveDirectory($root, $this->path($manifest['slug']));
        } finally {
            File::deleteDirectory($temporary);
        }

        $this->sync();

        return Plugin::query()->where('slug', $manifest['slug'])->firstOrFail();
    }

    /**
     * Removes the plugin's files and record. With $deleteData, its uninstall.php runs and its migrations are rolled back first.
     */
    public function uninstall(Plugin $plugin, bool $deleteData = false): void
    {
        if ($deleteData) {
            $this->runUninstallScript($plugin);

            if ($path = $this->migrationsPath($plugin)) {
                $this->app->make('migrator')->reset([$path]);
            }
        }

        File::deleteDirectory($this->path($plugin->slug));
        $plugin->delete();

        Hook::doAction('plugin.uninstalled', $plugin, $deleteData);
    }

    /**
     * Rejects archives with entries that could be extracted outside the target directory (zip slip).
     *
     * @throws PluginInstallException
     */
    protected function assertSafeEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);

            if (str_starts_with($name, '/') || str_contains($name, '\\') || in_array('..', explode('/', $name), true)) {
                throw new PluginInstallException(__('admin.plugins.errors.unsafe_archive'));
            }
        }
    }

    /**
     * Finds the directory holding plugin.json: the archive root or its only top-level directory (macOS metadata ignored).
     */
    protected function findRoot(string $directory): ?string
    {
        if (is_file($directory.'/plugin.json')) {
            return $directory;
        }

        $directories = array_values(array_filter(
            File::directories($directory),
            fn (string $path): bool => basename($path) !== '__MACOSX',
        ));

        return count($directories) === 1 && is_file($directories[0].'/plugin.json') ? $directories[0] : null;
    }

    /**
     * Accepts PSR-4 mappings of a namespace ending with a backslash to a relative path inside the plugin directory.
     *
     * @param  array<mixed, mixed>  $autoload
     */
    protected function isValidAutoload(array $autoload): bool
    {
        foreach ($autoload as $namespace => $path) {
            if (! is_string($namespace) || ! preg_match('/^[A-Za-z_]\w*(\\\\[A-Za-z_]\w*)*\\\\$/', $namespace)) {
                return false;
            }

            if (! is_string($path) || str_starts_with($path, '/') || in_array('..', explode('/', $path), true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Maps the plugin's PSR-4 namespaces to directories inside the plugin.
     *
     * @param  array<string, string>  $autoload
     */
    protected function registerAutoload(string $directory, array $autoload): void
    {
        if ($autoload === []) {
            return;
        }

        $loader = new ClassLoader;

        foreach ($autoload as $namespace => $path) {
            $loader->addPsr4($namespace, $directory.'/'.trim($path, '/'));
        }

        $loader->register();
    }

    /**
     * Registers and boots the plugin's service provider right away, so errors in its register() and boot()
     * are caught here instead of breaking the application later in the boot cycle.
     */
    protected function bootProvider(string $provider, string $slug): void
    {
        if (! is_subclass_of($provider, ServiceProvider::class)) {
            throw new RuntimeException("Plugin provider {$provider} does not exist or is not a service provider.");
        }

        $instance = new $provider($this->app);

        if ($instance instanceof PluginProvider) {
            $instance->setSlug($slug);
        }

        $instance->register();
        $instance->callBootingCallbacks();

        if (method_exists($instance, 'boot')) {
            $this->app->call([$instance, 'boot']);
        }

        $instance->callBootedCallbacks();
    }

    /**
     * Deactivates a plugin that failed, stores its error and logs it.
     */
    protected function fail(Plugin $plugin, Throwable $exception): false
    {
        $plugin->update(['is_active' => false, 'error' => $exception->getMessage()]);

        Log::error('Plugin failed to load and was deactivated.', [
            'plugin' => $plugin->slug,
            'exception' => $exception,
        ]);

        return false;
    }

    /**
     * Returns the plugin's migrations directory, or null when the plugin has no migrations.
     */
    protected function migrationsPath(Plugin $plugin): ?string
    {
        $path = $this->path($plugin->slug).'/database/migrations';

        return is_dir($path) ? $path : null;
    }

    protected function migrate(Plugin $plugin): void
    {
        if ($path = $this->migrationsPath($plugin)) {
            $this->app->make('migrator')->run([$path]);
        }
    }

    /**
     * Requires the plugin's uninstall.php, where it removes data its migrations do not cover (settings, files).
     * A failing script is logged and does not stop the removal.
     */
    protected function runUninstallScript(Plugin $plugin): void
    {
        $file = $this->path($plugin->slug).'/uninstall.php';

        if (! is_file($file)) {
            return;
        }

        try {
            (static function (string $file): void {
                require $file;
            })($file);
        } catch (Throwable $exception) {
            Log::error('Plugin uninstall script failed.', [
                'plugin' => $plugin->slug,
                'exception' => $exception,
            ]);
        }
    }
}
