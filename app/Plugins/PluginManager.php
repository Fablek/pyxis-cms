<?php

namespace App\Plugins;

use App\Models\Plugin;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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

    public function path(string $slug = ''): string
    {
        $base = rtrim((string) config('pyxis.plugins.path'), '/');

        return $slug === '' ? $base : "{$base}/{$slug}";
    }

    /**
     * Reads and validates the plugin.json manifest of a plugin directory.
     *
     * @return array{slug: string, name: string, version: string, description: ?string, author: ?string}|null
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

        return [
            'slug' => $manifest['slug'],
            'name' => $manifest['name'],
            'version' => $manifest['version'],
            'description' => is_string($manifest['description'] ?? null) ? $manifest['description'] : null,
            'author' => is_string($manifest['author'] ?? null) ? $manifest['author'] : null,
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
            Plugin::query()->updateOrCreate(['slug' => $slug], Arr::except($manifest, 'slug'));
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
     * Requires the plugin's plugin.php. A plugin that fails is deactivated and its error is stored, so it cannot break the CMS.
     */
    public function load(Plugin $plugin): bool
    {
        $file = $this->path($plugin->slug).'/plugin.php';

        try {
            if (! is_file($file)) {
                throw new RuntimeException('Missing plugin.php file.');
            }

            (static function (string $file): void {
                require_once $file;
            })($file);
        } catch (Throwable $exception) {
            $plugin->update(['is_active' => false, 'error' => $exception->getMessage()]);

            Log::error('Plugin failed to load and was deactivated.', [
                'plugin' => $plugin->slug,
                'exception' => $exception,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Activates a plugin and loads it right away, so a broken plugin is caught at activation instead of on the next request.
     */
    public function activate(Plugin $plugin): bool
    {
        $plugin->update(['is_active' => true, 'error' => null]);

        return $this->load($plugin);
    }

    public function deactivate(Plugin $plugin): void
    {
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
     * Removes the plugin's files and its record.
     */
    public function uninstall(Plugin $plugin): void
    {
        File::deleteDirectory($this->path($plugin->slug));

        $plugin->delete();
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
}
