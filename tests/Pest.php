<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => cache()->flush())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Points the plugins path at a fresh temporary directory and returns it.
 */
function usePluginsDirectory(): string
{
    $path = storage_path('framework/testing/plugins-'.uniqid());
    File::makeDirectory($path, recursive: true);
    config(['pyxis.plugins.path' => $path]);

    return $path;
}

/**
 * Builds a ZIP archive from a map of entry names to contents and returns its path.
 *
 * @param  array<string, string>  $files
 */
function makePluginArchive(array $files): string
{
    $path = config('pyxis.plugins.path').'/plugin-'.uniqid().'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);

    foreach ($files as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    $zip->close();

    return $path;
}

/**
 * Adds a migration creating the given table to a plugin.
 */
function makePluginMigration(string $slug, string $table): void
{
    $path = config('pyxis.plugins.path')."/{$slug}/database/migrations";
    File::makeDirectory($path, recursive: true);
    File::put("{$path}/2026_01_01_000000_create_{$table}_table.php", <<<PHP
        <?php

        use Illuminate\\Database\\Migrations\\Migration;
        use Illuminate\\Database\\Schema\\Blueprint;
        use Illuminate\\Support\\Facades\\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('{$table}', function (Blueprint \$table) {
                    \$table->id();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('{$table}');
            }
        };
        PHP);
}

/**
 * Creates a plugin directory with the given plugin.json (array or raw string).
 */
function makePlugin(string $directory, array|string $manifest): void
{
    $path = config('pyxis.plugins.path').'/'.$directory;
    File::makeDirectory($path, recursive: true);
    File::put($path.'/plugin.json', is_array($manifest) ? json_encode($manifest) : $manifest);
}

/**
 * Creates a plugin with a valid manifest and a plugin.php containing the given code.
 */
function makePluginFile(string $slug, string $code): void
{
    makePlugin($slug, ['name' => $slug, 'slug' => $slug, 'version' => '1.0.0']);
    File::put(config('pyxis.plugins.path')."/{$slug}/plugin.php", "<?php\n\n{$code}\n");
}
