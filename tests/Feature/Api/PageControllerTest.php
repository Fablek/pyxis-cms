<?php

use App\Enums\PageStatus;
use App\Enums\PageVisibility;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\Role;
use App\Models\Setting;
use App\Plugins\Hook;

function heroFieldGroup(array $fields = [['type' => 'text', 'label' => 'Tytuł', 'name' => 'title']]): FieldGroup
{
    return FieldGroup::factory()->create(['slug' => 'hero', 'fields' => $fields]);
}

it('returns the correct homepage based on global settings', function () {
    // Arrange
    // Set lang
    Setting::set('site_language', 'pl');

    // Make page. Factory will automatically create User and Role due to relationships.
    $homePage = Page::factory()->create([
        'title' => 'Witamy w Pyxis',
        'slug' => 'home',
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
        'parent_id' => $parent->id,
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
        'status' => PageStatus::PUBLISHED,
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
        'status' => PageStatus::DRAFT,
        'published_at' => now()->subDay(),
    ]);

    Setting::set('site_language', 'pl');

    // Act & Assert
    $this->getJson('/api/pages/draft-page')
        ->assertStatus(404);
});

it('returns 404 for private pages', function () {
    // Arrange: Page with private visibility
    $privatePage = Page::factory()->create([
        'slug' => 'private-page',
        'visibility' => PageVisibility::PRIVATE,
        'published_at' => now()->subDay(),
    ]);

    Setting::set('site_language', 'pl');

    // Act & Assert
    $this->getJson('/api/pages/private-page')
        ->assertStatus(404);
});

it('returns 200 but hides content for password protected pages', function () {
    // Arrange: Protected page
    $passwordPage = Page::factory()->create([
        'slug' => 'password-page',
        'status' => PageStatus::PUBLISHED,
        'visibility' => PageVisibility::PASSWORD,
        'content' => ['blocks' => 'this should be hidden'],
        'fields' => ['hero' => ['title' => 'this should be hidden too']],
    ]);

    Setting::set('site_language', 'pl');

    // Act & Assert
    $response = $this->getJson('/api/pages/password-page');

    $response->assertStatus(200)
        ->assertJsonPath('data.is_password_protected', true)
        ->assertJsonPath('data.content', null)
        ->assertJsonPath('data.fields', null);
});

it('returns 404 when homepage is requested but not set in settings', function () {
    // Arrange: Homepage is not set in settings
    Setting::set('site_language', 'pl');

    $this->getJson('/api/pages')
        ->assertStatus(404);
});
it('returns 403 when the preview token is invalid', function () {
    Page::factory()->create(['slug' => 'kontakt']);

    $response = $this->getJson('/api/pages/kontakt', [
        config('pyxis.preview.header') => 'not-a-valid-token',
    ]);

    $response->assertForbidden()
        ->assertJsonPath('message', 'Invalid or malformed preview token.');
});

it('returns published static field values', function () {
    heroFieldGroup();

    Page::factory()->create([
        'slug' => 'kontakt',
        'fields' => ['hero' => ['title' => 'Opublikowany']],
        'fields_draft' => ['hero' => ['title' => 'Szkic']],
    ]);

    $this->getJson('/api/pages/kontakt')
        ->assertOk()
        ->assertJsonPath('data.fields.hero.title', 'Opublikowany');
});

it('returns draft static field values in preview mode', function () {
    heroFieldGroup();

    Page::factory()->create([
        'slug' => 'kontakt',
        'fields' => ['hero' => ['title' => 'Opublikowany']],
        'fields_draft' => ['hero' => ['title' => 'Szkic']],
    ]);

    $this->getJson('/api/pages/kontakt', [
        config('pyxis.preview.header') => encrypt(config('pyxis.preview.secret')),
    ])
        ->assertOk()
        ->assertJsonPath('data.is_preview', true)
        ->assertJsonPath('data.fields.hero.title', 'Szkic');
});

it('returns static field values transformed for the frontend', function () {
    $linkedPage = Page::factory()->create(['slug' => 'oferta']);
    heroFieldGroup([
        ['type' => 'text', 'label' => 'Tytuł', 'name' => 'title'],
        ['type' => 'page', 'label' => 'Link', 'name' => 'cta'],
    ]);

    Page::factory()->create([
        'slug' => 'kontakt',
        'fields' => ['hero' => ['title' => 'Witaj', 'cta' => $linkedPage->id, 'removed_field' => 'x']],
    ]);

    $this->getJson('/api/pages/kontakt')
        ->assertOk()
        ->assertJsonPath('data.fields.hero.cta.full_url', '/oferta')
        ->assertJsonPath('data.fields.hero.cta.title', $linkedPage->title)
        ->assertJsonMissingPath('data.fields.hero.removed_field');
});

it('does not expose links to unpublished pages', function () {
    $draftPage = Page::factory()->create(['status' => PageStatus::DRAFT]);
    heroFieldGroup([['type' => 'page', 'label' => 'Link', 'name' => 'cta']]);

    Page::factory()->create([
        'slug' => 'kontakt',
        'fields' => ['hero' => ['cta' => $draftPage->id]],
    ]);

    $this->getJson('/api/pages/kontakt')
        ->assertOk()
        ->assertJsonPath('data.fields.hero.cta', null);
});

it('returns content blocks transformed and keeps unknown items unchanged', function () {
    $linkedPage = Page::factory()->create(['slug' => 'oferta']);
    heroFieldGroup([['type' => 'page', 'label' => 'Link', 'name' => 'cta']]);

    Page::factory()->create([
        'slug' => 'kontakt',
        'content' => [
            ['type' => 'hero', 'data' => ['cta' => $linkedPage->id]],
            ['type' => 'legacy', 'data' => ['raw' => 'unchanged']],
        ],
    ]);

    $this->getJson('/api/pages/kontakt')
        ->assertOk()
        ->assertJsonPath('data.content.0.type', 'hero')
        ->assertJsonPath('data.content.0.data.cta.full_url', '/oferta')
        ->assertJsonPath('data.content.1', ['type' => 'legacy', 'data' => ['raw' => 'unchanged']]);
});

it('lets plugins filter the page api response', function () {
    Hook::filter('page.api', fn (array $data, Page $page): array => [...$data, 'title_length' => mb_strlen($page->title)]);

    Page::factory()->create(['slug' => 'kontakt', 'title' => 'Kontakt']);

    $this->getJson('/api/pages/kontakt')
        ->assertOk()
        ->assertJsonPath('data.title_length', 7);
});

it('returns the published seo with the share image as media', function () {
    Storage::fake('public');
    $image = storedImage('og', 'Okładka');

    Page::factory()->create([
        'slug' => 'kontakt',
        'seo' => ['title' => 'Opublikowany', 'description' => 'Opis', 'og_image' => $image->id, 'noindex' => true],
        'seo_draft' => ['title' => 'Szkic'],
    ]);

    $this->getJson('/api/pages/kontakt')
        ->assertOk()
        ->assertJsonPath('data.seo.title', 'Opublikowany')
        ->assertJsonPath('data.seo.description', 'Opis')
        ->assertJsonPath('data.seo.og_image.id', $image->id)
        ->assertJsonPath('data.seo.og_image.alt', 'Okładka')
        ->assertJsonPath('data.seo.noindex', true);
});

it('returns the draft seo in preview mode', function () {
    Page::factory()->create([
        'slug' => 'kontakt',
        'seo' => ['title' => 'Opublikowany'],
        'seo_draft' => ['title' => 'Szkic'],
    ]);

    $this->getJson('/api/pages/kontakt', [
        config('pyxis.preview.header') => encrypt(config('pyxis.preview.secret')),
    ])
        ->assertOk()
        ->assertJsonPath('data.seo.title', 'Szkic');
});

it('returns empty seo when the page has none', function () {
    Page::factory()->create(['slug' => 'kontakt']);

    $this->getJson('/api/pages/kontakt')
        ->assertOk()
        ->assertJsonPath('data.seo', ['title' => null, 'description' => null, 'og_image' => null, 'noindex' => false]);
});
