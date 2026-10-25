<?php

use App\Models\Plugin;
use App\Plugins\Hook;
use App\Plugins\PluginInstallException;
use App\Plugins\PluginManager;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->pluginsPath = usePluginsDirectory();
});

afterEach(function () {
    File::deleteDirectory($this->pluginsPath);
});

function makePlugin(string $directory, array|string $manifest): void
{
    $path = config('pyxis.plugins.path').'/'.$directory;
    File::makeDirectory($path, recursive: true);
    File::put($path.'/plugin.json', is_array($manifest) ? json_encode($manifest) : $manifest);
}

function makePluginFile(string $slug, string $code): void
{
    makePlugin($slug, ['name' => $slug, 'slug' => $slug, 'version' => '1.0.0']);
    File::put(config('pyxis.plugins.path')."/{$slug}/plugin.php", "<?php\n\n{$code}\n");
}

function manifestJson(string $slug): string
{
    return json_encode(['name' => 'Hello Pyxis', 'slug' => $slug, 'version' => '1.0.0']);
}

it('registers new plugins as inactive', function () {
    makePlugin('hello-pyxis', ['name' => 'Hello Pyxis', 'slug' => 'hello-pyxis', 'version' => '1.0.0', 'author' => 'Pyxis']);

    app(PluginManager::class)->sync();

    expect(Plugin::firstWhere('slug', 'hello-pyxis'))
        ->name->toBe('Hello Pyxis')
        ->version->toBe('1.0.0')
        ->author->toBe('Pyxis')
        ->is_active->toBeFalse();
});

it('skips plugins with an invalid manifest', function (array|string $manifest) {
    makePlugin('broken', $manifest);

    expect(app(PluginManager::class)->sync())->toBeEmpty();
})->with([
    'invalid json' => ['not json'],
    'missing version' => [['name' => 'Broken', 'slug' => 'broken']],
    'slug different from the directory' => [['name' => 'Broken', 'slug' => 'other', 'version' => '1.0.0']],
    'slug with forbidden characters' => [['name' => 'Broken', 'slug' => '../broken', 'version' => '1.0.0']],
]);

it('keeps the activation state and refreshes metadata on resync', function () {
    Plugin::factory()->create(['slug' => 'hello-pyxis', 'version' => '1.0.0', 'is_active' => true]);
    makePlugin('hello-pyxis', ['name' => 'Hello Pyxis', 'slug' => 'hello-pyxis', 'version' => '1.1.0']);

    app(PluginManager::class)->sync();

    expect(Plugin::firstWhere('slug', 'hello-pyxis'))
        ->version->toBe('1.1.0')
        ->is_active->toBeTrue();
});

it('removes plugins whose directory no longer exists', function () {
    Plugin::factory()->create(['slug' => 'removed-plugin']);

    app(PluginManager::class)->sync();

    expect(Plugin::query()->where('slug', 'removed-plugin')->exists())->toBeFalse();
});

it('loads active plugins so they can register hooks', function () {
    makePluginFile('shout', "App\\Plugins\\Hook::filter('test.title', fn (string \$title): string => strtoupper(\$title));");
    Plugin::factory()->create(['slug' => 'shout', 'is_active' => true]);

    app(PluginManager::class)->loadActive();

    expect(Hook::applyFilters('test.title', 'pyxis'))->toBe('PYXIS');
});

it('does not load inactive plugins', function () {
    makePluginFile('shout', "App\\Plugins\\Hook::filter('test.title', fn (string \$title): string => strtoupper(\$title));");
    Plugin::factory()->create(['slug' => 'shout', 'is_active' => false]);

    app(PluginManager::class)->loadActive();

    expect(Hook::applyFilters('test.title', 'pyxis'))->toBe('pyxis');
});

it('deactivates a plugin that fails to load and stores the error', function (string $code) {
    makePluginFile('broken', $code);
    $plugin = Plugin::factory()->create(['slug' => 'broken', 'is_active' => true]);

    expect(app(PluginManager::class)->load($plugin))->toBeFalse()
        ->and($plugin->fresh())
        ->is_active->toBeFalse()
        ->error->not->toBeEmpty();
})->with([
    'exception' => ["throw new RuntimeException('Boom');"],
    'syntax error' => ['this is not php'],
]);

it('deactivates a plugin without a plugin.php file', function () {
    makePlugin('empty', ['name' => 'Empty', 'slug' => 'empty', 'version' => '1.0.0']);
    $plugin = Plugin::factory()->create(['slug' => 'empty', 'is_active' => true]);

    app(PluginManager::class)->load($plugin);

    expect($plugin->fresh())->is_active->toBeFalse()
        ->error->toBe('Missing plugin.php file.');
});

it('installs a plugin from an archive', function (array $files) {
    $plugin = app(PluginManager::class)->install(makePluginArchive($files));

    expect($plugin)->slug->toBe('hello-pyxis')->is_active->toBeFalse()
        ->and(File::exists($this->pluginsPath.'/hello-pyxis/plugin.php'))->toBeTrue();
})->with([
    'files at the root' => [['plugin.json' => manifestJson('hello-pyxis'), 'plugin.php' => '<?php']],
    'files in a top-level directory' => [['hello-pyxis-main/plugin.json' => manifestJson('hello-pyxis'), 'hello-pyxis-main/plugin.php' => '<?php']],
]);

it('rejects archives that cannot be installed', function (array $files) {
    app(PluginManager::class)->install(makePluginArchive($files));
})->with([
    'path traversal' => [['plugin.json' => manifestJson('hello-pyxis'), '../evil.php' => '<?php']],
    'missing manifest' => [['plugin.php' => '<?php']],
    'invalid slug' => [['plugin.json' => manifestJson('../hello')]],
])->throws(PluginInstallException::class);

it('does not overwrite an installed plugin', function () {
    makePlugin('hello-pyxis', manifestJson('hello-pyxis'));

    app(PluginManager::class)->install(makePluginArchive(['plugin.json' => manifestJson('hello-pyxis')]));
})->throws(PluginInstallException::class);

it('removes the files and the record on uninstall', function () {
    makePlugin('hello-pyxis', manifestJson('hello-pyxis'));
    $plugin = Plugin::factory()->create(['slug' => 'hello-pyxis']);

    app(PluginManager::class)->uninstall($plugin);

    expect(File::isDirectory($this->pluginsPath.'/hello-pyxis'))->toBeFalse()
        ->and(Plugin::query()->whereKey($plugin->id)->exists())->toBeFalse();
});
