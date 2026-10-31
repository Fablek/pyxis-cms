<?php

use App\Enums\FieldGroupMode;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\User;
use App\Plugins\Hook;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Builder;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use App\Ai\Agents\SeoWriter;
use App\Models\Setting;
use App\Services\AiSettings;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Exceptions\ProviderOverloadedException;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    // Publishing notifies the frontend about revalidation.
    Http::fake();
});

function heroGroupFor(string $param, string $value): FieldGroup
{
    return FieldGroup::factory()->create([
        'title' => 'Hero',
        'slug' => 'hero',
        'fields' => [['type' => 'text', 'label' => 'Nagłówek', 'name' => 'title']],
        'rules' => [['conditions' => [['param' => $param, 'operator' => '==', 'value' => $value]]]],
    ]);
}

/**
 * @return array<string>
 */
function pickerBlocks(Testable $livewire): array
{
    $builder = collect($livewire->instance()->getSchema('form')->getFlatComponents(withHidden: true))
        ->first(fn ($component): bool => $component instanceof Builder);

    return array_values(array_map(fn ($block): string => $block->getName(), $builder->getBlockPickerBlocks()));
}

it('shows only field groups matching the page', function () {
    $page = Page::factory()->create();
    heroGroupFor('page', $page->id);
    FieldGroup::factory()->create([
        'title' => 'Stopka',
        'rules' => [['conditions' => [['param' => 'page', 'operator' => '==', 'value' => Page::factory()->create()->id]]]],
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertSee('Hero')
        ->assertDontSee('Stopka');
});

it('shows field groups matching an unsaved parent change', function () {
    $parent = Page::factory()->create();
    $page = Page::factory()->create();
    heroGroupFor('page_parent', $parent->id);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertDontSee('Hero')
        ->fillForm(['parent_id' => $parent->id])
        ->assertSee('Hero');
});

it('saves field values to the draft only', function () {
    $page = Page::factory()->create();
    heroGroupFor('page', $page->id);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['fields_draft' => ['hero' => ['title' => 'Witaj']]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh())
        ->fields_draft->toBe(['hero' => ['title' => 'Witaj']])
        ->fields->toBeNull();
});

it('copies draft field values to the published fields on publish', function () {
    $page = Page::factory()->create();
    heroGroupFor('page', $page->id);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['fields_draft' => ['hero' => ['title' => 'Witaj']]])
        ->callAction(TestAction::make('publish')->schemaComponent('pageActions'));

    expect($page->fresh()->fields)->toBe(['hero' => ['title' => 'Witaj']]);
});

it('copies the draft seo to the published seo on publish', function () {
    $page = Page::factory()->create();

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['seo_draft' => ['title' => 'Tytuł SEO']])
        ->callAction(TestAction::make('publish')->schemaComponent('pageActions'));

    expect($page->fresh()->seo)->toMatchArray(['title' => 'Tytuł SEO']);
});

it('offers block groups in the content builder instead of static sections', function () {
    $page = Page::factory()->create();
    heroGroupFor('page', $page->id)->update(['mode' => FieldGroupMode::BLOCK]);

    $livewire = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()]);

    expect(pickerBlocks($livewire))->toBe(['hero']);
});

it('does not offer static groups as content blocks', function () {
    $page = Page::factory()->create();
    heroGroupFor('page', $page->id);

    $livewire = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertSee('Hero');

    expect(pickerBlocks($livewire))->toBe([]);
});

it('keeps blocks of groups that are no longer available but stops offering them', function () {
    Builder::fake();

    $page = Page::factory()->create([
        'content_draft' => [['type' => 'hero', 'data' => ['title' => 'Witaj']]],
    ]);
    heroGroupFor('page', Page::factory()->create()->id)->update(['mode' => FieldGroupMode::BLOCK]);

    $livewire = Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh()->content_draft)->toBe([['type' => 'hero', 'data' => ['title' => 'Witaj']]])
        ->and(pickerBlocks($livewire))->toBe([]);
});

it('runs the page.published action on publish', function () {
    $page = Page::factory()->create();
    $published = [];
    Hook::action('page.published', function (Page $page) use (&$published): void {
        $published[] = $page->id;
    });

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction(TestAction::make('publish')->schemaComponent('pageActions'));

    expect($published)->toBe([$page->id]);
});

it('saves seo to the draft only', function () {
    $page = Page::factory()->create();

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['seo_draft' => ['title' => 'Tytuł SEO', 'description' => 'Opis', 'noindex' => true]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh())
        ->seo_draft->toMatchArray(['title' => 'Tytuł SEO', 'description' => 'Opis', 'noindex' => true])
        ->seo->toBeNull();
});

function enableAi(): void
{
    Setting::set('ai_enabled', true);
    Setting::set('ai_provider', 'anthropic');
    Setting::set('ai_model', 'test-model');
    app(AiSettings::class)->storeApiKey('sk-test-key');
}

it('hides the seo generation when ai is disabled', function () {
    $page = Page::factory()->create();

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->assertDontSee(__('admin.pages.actions.generate_seo'));
});

it('fills the seo form from the page text without saving it', function () {
    enableAi();
    SeoWriter::fake([['title' => 'Tytuł AI', 'description' => 'Opis AI']]);
    $page = Page::factory()->create(['content_draft' => [['type' => 'hero', 'data' => ['title' => 'Treść bloku']]]]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction(TestAction::make('generateSeo')->schemaComponent('seoActions'))
        ->assertSchemaStateSet(['seo_draft.title' => 'Tytuł AI', 'seo_draft.description' => 'Opis AI'])
        ->assertNotified(__('admin.pages.notifications.seo_generated'));

    SeoWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Treść bloku')
        && ! $prompt->contains('hero')
        && $prompt->model === 'test-model');

    expect($page->fresh()->seo_draft)->toBeNull()
        ->and(config('ai.providers.anthropic.key'))->toBe('sk-test-key');
});

it('does not call ai for a page without text', function () {
    enableAi();
    SeoWriter::fake();
    $page = Page::factory()->create(['content_draft' => []]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction(TestAction::make('generateSeo')->schemaComponent('seoActions'))
        ->assertNotified(__('admin.pages.notifications.seo_no_content'));

    SeoWriter::assertNeverPrompted();
});

it('keeps the seo form and notifies when the provider fails', function () {
    enableAi();
    SeoWriter::fake(fn () => throw new RuntimeException('Provider error'));
    $page = Page::factory()->create([
        'content_draft' => [['type' => 'hero', 'data' => ['title' => 'Treść bloku']]],
        'seo_draft' => ['title' => 'Mój tytuł'],
    ]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction(TestAction::make('generateSeo')->schemaComponent('seoActions'))
        ->assertSchemaStateSet(['seo_draft.title' => 'Mój tytuł'])
        ->assertNotified(__('admin.pages.notifications.seo_failed'));
});

it('asks to try again later when the provider is overloaded', function () {
    enableAi();
    SeoWriter::fake(fn () => throw new ProviderOverloadedException('Overloaded'));
    $page = Page::factory()->create(['content_draft' => [['type' => 'hero', 'data' => ['title' => 'Treść bloku']]]]);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->callAction(TestAction::make('generateSeo')->schemaComponent('seoActions'))
        ->assertNotified(__('admin.pages.notifications.seo_unavailable'));
});
