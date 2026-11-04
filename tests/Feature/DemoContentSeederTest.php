<?php

use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('creates a published demo site with the homepage, subpages and images', function () {
    User::factory()->admin()->create();

    $this->seed(DemoContentSeeder::class);

    expect(Page::query()->count())->toBe(8)
        ->and(Setting::get('homepage_id'))->toBe(Page::query()->where('title', 'Pyxis CMS')->value('id'));

    Storage::disk('public')->assertExists('media/dla-deweloperow.webp');

    $this->getJson('/api/pages')
        ->assertOk()
        ->assertJsonPath('data.content.0.type', 'hero')
        ->assertJsonPath('data.content.2.data.elementy.2.strona.full_url', '/funkcje/asystent-ai')
        ->assertJsonPath('data.content.3.data.elementy.0.zdjecie.alt', 'Laptop z kodem na biurku');

    $this->getJson('/api/pages/funkcje/asystent-ai')->assertOk();
});

it('uses every core field type and both field group modes', function () {
    User::factory()->admin()->create();

    $this->seed(DemoContentSeeder::class);

    $types = FieldGroup::query()->get()
        ->flatMap(fn (FieldGroup $group): array => $group->fields)
        ->flatMap(fn (array $field): array => [$field['type'], ...array_column($field['sub_fields'] ?? [], 'type')])
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($types)->toBe(['gallery', 'group', 'image', 'link', 'number', 'page', 'repeater', 'select', 'text', 'textarea', 'toggle', 'wysiwyg']);

    $this->getJson('/api/pages/kontakt')
        ->assertOk()
        ->assertJsonPath('data.fields.kontakt.uczelnia.nazwa', 'Politechnika Śląska');
});

it('leaves an installation with pages untouched', function () {
    User::factory()->admin()->create();
    $page = Page::factory()->create();

    $this->seed(DemoContentSeeder::class);

    expect(Page::query()->pluck('id')->all())->toBe([$page->id])
        ->and(FieldGroup::query()->count())->toBe(0);
});
