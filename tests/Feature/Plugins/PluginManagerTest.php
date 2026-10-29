<?php

use App\Models\Plugin;
use App\Plugins\Hook;
use App\Plugins\PluginInstallException;
use App\Plugins\PluginManager;
use Filament\Contracts\Plugin as FilamentPlugin;
use Filament\Panel;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->pluginsPath = usePluginsDirectory();
});

afterEach(function () {
    File::deleteDirectory($this->pluginsPath);
});

function manifestJson(string $slug): string
{
    return json_encode(['name' => 'Hello Pyxis', 'slug' => $slug, 'version' => '1.0.0']);
}

/**
 * Minimal Filament plugin used to check that plugins can extend the admin panel.
 */
class FakeAdminPlugin implements FilamentPlugin
{
    public function getId(): string
    {
        return 'fake-admin';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}

/**
 * Creates a plugin with a service provider whose boot() runs the given code; returns the provider class.
 */
function makeProviderPlugin(string $slug, string $bootCode): string
{
    $namespace = 'TestPlugin'.str_replace('.', '', uniqid('', true));

    makePlugin($slug, [
        'name' => $slug,
        'slug' => $slug,
        'version' => '1.0.0',
        'autoload' => ["{$namespace}\\" => 'src/'],
        'provider' => "{$namespace}\\PluginProvider",
    ]);

    File::makeDirectory(config('pyxis.plugins.path')."/{$slug}/src");
    File::put(config('pyxis.plugins.path')."/{$slug}/src/PluginProvider.php", <<<PHP
        <?php

        namespace {$namespace};

        class PluginProvider extends \\App\\Plugins\\PluginProvider
        {
            public function boot(): void
            {
                {$bootCode}
            }
        }
        PHP);

    return "{$namespace}\\PluginProvider";
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
    'autoload outside the plugin' => [['name' => 'Broken', 'slug' => 'broken', 'version' => '1.0.0', 'autoload' => ['Broken\\' => '../src/']]],
    'autoload namespace without a trailing backslash' => [['name' => 'Broken', 'slug' => 'broken', 'version' => '1.0.0', 'autoload' => ['Broken' => 'src/']]],
    'provider without autoload' => [['name' => 'Broken', 'slug' => 'broken', 'version' => '1.0.0', 'provider' => 'Broken\\Provider']],
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

it('reads autoload and provider from the manifest', function () {
    makePlugin('contact-form', [
        'name' => 'Contact Form',
        'slug' => 'contact-form',
        'version' => '1.0.0',
        'autoload' => ['Pyxis\\ContactForm\\' => 'src/'],
        'provider' => 'Pyxis\\ContactForm\\ContactFormServiceProvider',
    ]);

    expect(app(PluginManager::class)->readManifest($this->pluginsPath.'/contact-form'))
        ->autoload->toBe(['Pyxis\\ContactForm\\' => 'src/'])
        ->provider->toBe('Pyxis\\ContactForm\\ContactFormServiceProvider');
});

it('autoloads and boots the plugin provider', function () {
    makeProviderPlugin('provided', "\\App\\Plugins\\Hook::filter('test.title', fn (string \$title): string => strtoupper(\$title));");
    $plugin = Plugin::factory()->create(['slug' => 'provided', 'is_active' => true]);

    expect(app(PluginManager::class)->load($plugin))->toBeTrue()
        ->and(Hook::applyFilters('test.title', 'pyxis'))->toBe('PYXIS');
});

it('deactivates a plugin whose provider fails to boot', function () {
    makeProviderPlugin('failing', "throw new \\RuntimeException('Provider boom');");
    $plugin = Plugin::factory()->create(['slug' => 'failing', 'is_active' => true]);

    app(PluginManager::class)->load($plugin);

    expect($plugin->fresh())
        ->is_active->toBeFalse()
        ->error->toBe('Provider boom');
});

it('deactivates a plugin whose provider class does not exist', function () {
    makePlugin('ghost', [
        'name' => 'Ghost',
        'slug' => 'ghost',
        'version' => '1.0.0',
        'autoload' => ['Ghost\\' => 'src/'],
        'provider' => 'Ghost\\MissingProvider',
    ]);
    $plugin = Plugin::factory()->create(['slug' => 'ghost', 'is_active' => true]);

    app(PluginManager::class)->load($plugin);

    expect($plugin->fresh())
        ->is_active->toBeFalse()
        ->error->toContain('Ghost\\MissingProvider');
});

it('registers plugin routes under its own prefix', function () {
    makeProviderPlugin('provided', "\$this->loadPluginRoutes(\$this->pluginPath('routes.php'));");
    File::put($this->pluginsPath.'/provided/routes.php', <<<'PHP'
        <?php

        use Illuminate\Support\Facades\Route;

        Route::get('ping', fn () => ['pong' => true]);
        PHP);
    $plugin = Plugin::factory()->create(['slug' => 'provided', 'is_active' => true]);

    app(PluginManager::class)->load($plugin);

    $this->getJson('/api/plugins/provided/ping')->assertOk()->assertExactJson(['pong' => true]);
});

it('adds a plugin to the admin panel through the admin.panel filter', function () {
    makeProviderPlugin('provided', '$this->registerAdminPlugin(new \FakeAdminPlugin);');
    $plugin = Plugin::factory()->create(['slug' => 'provided', 'is_active' => true]);

    app(PluginManager::class)->load($plugin);

    expect(Hook::applyFilters('admin.panel', Panel::make()->id('test'))->hasPlugin('fake-admin'))->toBeTrue();
});

it('runs plugin migrations on activation', function () {
    makePluginFile('migrated', '');
    makePluginMigration('migrated', 'migrated_items');
    $plugin = Plugin::factory()->create(['slug' => 'migrated']);

    expect(app(PluginManager::class)->activate($plugin))->toBeTrue()
        ->and(Schema::hasTable('migrated_items'))->toBeTrue();
});

it('rolls back plugin migrations when uninstalled with its data', function () {
    makePluginFile('migrated', '');
    makePluginMigration('migrated', 'migrated_items');
    $plugin = Plugin::factory()->create(['slug' => 'migrated']);
    app(PluginManager::class)->activate($plugin);

    app(PluginManager::class)->uninstall($plugin->fresh(), deleteData: true);

    expect(Schema::hasTable('migrated_items'))->toBeFalse()
        ->and(Schema::hasTable('pages'))->toBeTrue();
});

it('keeps plugin tables when uninstalled without its data', function () {
    makePluginFile('migrated', '');
    makePluginMigration('migrated', 'migrated_items');
    $plugin = Plugin::factory()->create(['slug' => 'migrated']);
    app(PluginManager::class)->activate($plugin);

    app(PluginManager::class)->uninstall($plugin->fresh());

    expect(Schema::hasTable('migrated_items'))->toBeTrue();
});

it('does not activate a plugin whose migration fails', function () {
    makePluginFile('migrated', '');
    makePluginMigration('migrated', 'pages');
    $plugin = Plugin::factory()->create(['slug' => 'migrated']);

    expect(app(PluginManager::class)->activate($plugin))->toBeFalse()
        ->and($plugin->fresh())
        ->is_active->toBeFalse()
        ->error->not->toBeNull();
});

it('lets a plugin react to its own activation and deactivation', function () {
    makePluginFile('lifecycle', <<<'PHP'
        use App\Plugins\Hook;

        Hook::action('plugin.activated', fn ($plugin) => config(['lifecycle.activated' => $plugin->slug]));
        Hook::action('plugin.deactivated', fn ($plugin) => config(['lifecycle.deactivated' => $plugin->slug]));
        PHP);
    $plugin = Plugin::factory()->create(['slug' => 'lifecycle']);

    app(PluginManager::class)->activate($plugin);
    app(PluginManager::class)->deactivate($plugin);

    expect(config('lifecycle.activated'))->toBe('lifecycle')
        ->and(config('lifecycle.deactivated'))->toBe('lifecycle');
});

it('does not announce the activation of a plugin that fails to load', function () {
    makePluginFile('broken', "throw new RuntimeException('Boom');");
    $plugin = Plugin::factory()->create(['slug' => 'broken']);
    Hook::action('plugin.activated', fn () => config(['lifecycle.activated' => true]));

    app(PluginManager::class)->activate($plugin);

    expect(config('lifecycle.activated'))->toBeNull();
});

it('runs uninstall.php and announces the removal when deleting plugin data', function () {
    makePluginFile('lifecycle', '');
    File::put($this->pluginsPath.'/lifecycle/uninstall.php', "<?php\n\nconfig(['lifecycle.cleaned' => true]);\n");
    $plugin = Plugin::factory()->create(['slug' => 'lifecycle']);
    Hook::action('plugin.uninstalled', fn ($plugin, bool $deleteData) => config(['lifecycle.uninstalled' => $deleteData]));

    app(PluginManager::class)->uninstall($plugin, deleteData: true);

    expect(config('lifecycle.cleaned'))->toBeTrue()
        ->and(config('lifecycle.uninstalled'))->toBeTrue();
});

it('skips uninstall.php when keeping plugin data', function () {
    makePluginFile('lifecycle', '');
    File::put($this->pluginsPath.'/lifecycle/uninstall.php', "<?php\n\nconfig(['lifecycle.cleaned' => true]);\n");
    $plugin = Plugin::factory()->create(['slug' => 'lifecycle']);

    app(PluginManager::class)->uninstall($plugin);

    expect(config('lifecycle.cleaned'))->toBeNull()
        ->and(File::isDirectory($this->pluginsPath.'/lifecycle'))->toBeFalse();
});

it('removes a plugin even when its uninstall.php fails', function () {
    makePluginFile('lifecycle', '');
    File::put($this->pluginsPath.'/lifecycle/uninstall.php', "<?php\n\nthrow new RuntimeException('Cleanup boom');\n");
    $plugin = Plugin::factory()->create(['slug' => 'lifecycle']);

    app(PluginManager::class)->uninstall($plugin, deleteData: true);

    expect(Plugin::query()->whereKey($plugin->id)->exists())->toBeFalse()
        ->and(File::isDirectory($this->pluginsPath.'/lifecycle'))->toBeFalse();
});
