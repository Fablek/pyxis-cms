<?php

use App\Filament\Resources\Plugins\Pages\ManagePlugins;
use App\Filament\Resources\Plugins\PluginResource;
use App\Models\Plugin;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    $this->pluginsPath = usePluginsDirectory();
});

afterEach(function () {
    File::deleteDirectory($this->pluginsPath);
});

it('is available only to admins', function () {
    $this->actingAs(User::factory()->create())
        ->get(PluginResource::getUrl())
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(PluginResource::getUrl())
        ->assertSuccessful();
});

it('lists plugins found in the plugins directory', function () {
    $this->actingAs(User::factory()->admin()->create());
    makePlugin('hello-pyxis', ['name' => 'Hello Pyxis', 'slug' => 'hello-pyxis', 'version' => '1.0.0']);

    Livewire::test(ManagePlugins::class)
        ->assertCanSeeTableRecords(Plugin::query()->where('slug', 'hello-pyxis')->get());
});

it('activates and deactivates a plugin', function () {
    $this->actingAs(User::factory()->admin()->create());
    makePluginFile('hello-pyxis', '');
    $plugin = Plugin::factory()->create(['slug' => 'hello-pyxis']);

    Livewire::test(ManagePlugins::class)
        ->callAction(TestAction::make('activate')->table($plugin))
        ->assertNotified(__('admin.plugins.notifications.activated'));

    expect($plugin->fresh()->is_active)->toBeTrue();

    Livewire::test(ManagePlugins::class)
        ->callAction(TestAction::make('deactivate')->table($plugin))
        ->assertNotified(__('admin.plugins.notifications.deactivated'));

    expect($plugin->fresh()->is_active)->toBeFalse();
});

it('keeps a broken plugin inactive and shows its error', function () {
    $this->actingAs(User::factory()->admin()->create());
    makePluginFile('broken', "throw new RuntimeException('Boom');");
    $plugin = Plugin::factory()->create(['slug' => 'broken']);

    Livewire::test(ManagePlugins::class)
        ->callAction(TestAction::make('activate')->table($plugin))
        ->assertNotified(__('admin.plugins.notifications.activation_failed'));

    expect($plugin->fresh())
        ->is_active->toBeFalse()
        ->error->toBe('Boom');
});

it('installs a plugin from an uploaded archive', function () {
    $this->actingAs(User::factory()->admin()->create());
    $archive = makePluginArchive(['plugin.json' => json_encode(['name' => 'Hello Pyxis', 'slug' => 'hello-pyxis', 'version' => '1.0.0'])]);

    Livewire::test(ManagePlugins::class)
        ->callAction('upload', ['archive' => UploadedFile::fake()->createWithContent('hello-pyxis.zip', file_get_contents($archive))])
        ->assertHasNoActionErrors()
        ->assertNotified(__('admin.plugins.notifications.installed', ['name' => 'Hello Pyxis']));

    expect(Plugin::query()->where('slug', 'hello-pyxis')->exists())->toBeTrue();
});

it('shows why an uploaded archive could not be installed', function () {
    $this->actingAs(User::factory()->admin()->create());
    $archive = makePluginArchive(['plugin.php' => '<?php']);

    Livewire::test(ManagePlugins::class)
        ->callAction('upload', ['archive' => UploadedFile::fake()->createWithContent('broken.zip', file_get_contents($archive))])
        ->assertNotified(__('admin.plugins.notifications.install_failed'));

    expect(Plugin::query()->count())->toBe(0);
});

it('deletes an inactive plugin with its files', function () {
    $this->actingAs(User::factory()->admin()->create());
    makePluginFile('hello-pyxis', '');
    $plugin = Plugin::factory()->create(['slug' => 'hello-pyxis']);

    Livewire::test(ManagePlugins::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($plugin));

    expect(Plugin::query()->whereKey($plugin->id)->exists())->toBeFalse()
        ->and(File::isDirectory($this->pluginsPath.'/hello-pyxis'))->toBeFalse();
});

it('has every plugin translation in both languages', function (string $locale) {
    app()->setLocale($locale);

    foreach (['notifications.installed', 'notifications.install_failed', 'errors.invalid_manifest', 'errors.already_installed'] as $key) {
        expect(__("admin.plugins.{$key}"))->not->toBe("admin.plugins.{$key}");
    }
})->with(['pl', 'en']);
