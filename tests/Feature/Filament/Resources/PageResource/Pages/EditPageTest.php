<?php

use App\Enums\FieldGroupMode;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Builder;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

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
