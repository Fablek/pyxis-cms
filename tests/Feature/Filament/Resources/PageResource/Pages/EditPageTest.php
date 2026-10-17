<?php

use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Http;
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
