<?php

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('creates published demo pages with the homepage and images', function () {
    User::factory()->admin()->create();

    $this->seed(DemoContentSeeder::class);

    expect(Page::query()->pluck('title')->all())->toEqualCanonicalizing(['Pyxis CMS', 'O projekcie', 'Architektura', 'Kontakt'])
        ->and(Setting::get('homepage_id'))->toBe(Page::query()->where('title', 'Pyxis CMS')->value('id'));

    Storage::disk('public')->assertExists('media/edytor-strony.webp');

    $this->getJson('/api/pages/o-projekcie/architektura')->assertOk();
    $this->getJson('/api/pages')
        ->assertOk()
        ->assertJsonPath('data.content.0.type', 'hero')
        ->assertJsonPath('data.content.2.data.obrazek.alt', 'Edytor strony z blokami treści');
});

it('leaves an installation with pages untouched', function () {
    User::factory()->admin()->create();
    $page = Page::factory()->create();

    $this->seed(DemoContentSeeder::class);

    expect(Page::query()->pluck('id')->all())->toBe([$page->id]);
});
