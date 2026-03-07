<?php

use App\Models\Page;
use App\Models\Setting;
use App\Models\Role;

it('returns the correct homepage based on global settings', function () {
    // Arrange
    // Set lang
    Setting::set('site_language', 'pl');

    // Make page. Factory will automatically create User and Role due to relationships.
    $homePage = Page::factory()->create([
        'title' => 'Witamy w Pyxis',
        'slug' => 'home'
    ]);

    // Setup this page as main page
    Setting::set('homepage_id', $homePage->id);

    // Act
    $response = $this->getJson('/api/pages');

    // Assert
    $response->assertStatus(200)
        ->assertJsonPath('data.title', 'Witamy w Pyxis')
        ->assertJsonPath('data.full_url', '/');
});

it('validates the hierarchical path correctly', function () {
    // Arrange: Create tree of pages
    $parent = Page::factory()->create(['slug' => 'o-nas']);
    $child = Page::factory()->create([
        'title' => 'Nasz Zespół',
        'slug' => 'zespol',
        'parent_id' => $parent->id
    ]);

    // Set lang (middeware need it)
    Setting::set('site_language', 'pl');

    // Act and assert

    // Scenario A: wrong slug (without parent)
    $this->getJson('/api/pages/zespol')
        ->assertStatus(404);

    // Scenario B: correct slug
    $this->getJson('/api/pages/o-nas/zespol')
        ->assertStatus(200)
        ->assertJsonPath('data.title', 'Nasz Zespół')
        ->assertJsonPath('data.full_url', '/o-nas/zespol');
});

it('returns 404 for pages scheduled for the future', function () {
    // Arrange: Page will be post tommorow
    $fururePage = Page::factory()->create([
        'slug' => 'future',
        'status' => 'published',
        'published_at' => now()->addDay(),
    ]);

    Setting::set('site_language', 'pl');

    // Act & Assert
    $this->getJson('/api/pages/future')
        ->assertStatus(404);
});

it('returns 404 for draft pages', function () {
    // Arrange: Page with draft status
    $draftPage = Page::factory()->create([
        'slug' => 'draft-page',
        'status' => 'draft',
        'published_at' => now()->subDay(),
    ]);

    Setting::set('site_language', 'pl');

    // Act & Assert
    $this->getJson('/api/pages/draft-page')
        ->assertStatus(404);
});