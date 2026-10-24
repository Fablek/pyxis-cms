<?php

use App\Enums\FieldGroupMode;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\User;
use Filament\Forms\Components\Builder;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('saves static fields and content blocks when creating a page', function () {
    Builder::fake();

    $fields = [['type' => 'text', 'label' => 'Tytuł', 'name' => 'title']];
    $topLevelPages = [['conditions' => [['param' => 'page_type', 'operator' => '==', 'value' => 'top_level']]]];

    FieldGroup::factory()->create(['title' => 'Kontakt', 'slug' => 'kontakt', 'fields' => $fields, 'rules' => $topLevelPages]);
    FieldGroup::factory()->create(['title' => 'Hero', 'slug' => 'hero', 'mode' => FieldGroupMode::BLOCK, 'fields' => $fields, 'rules' => $topLevelPages]);

    Livewire::test(CreatePage::class)
        ->assertSee('Kontakt')
        ->fillForm([
            'title' => 'Nowa strona',
            'slug' => 'nowa-strona',
            'status' => 'draft',
            'visibility' => 'public',
            'fields_draft' => ['kontakt' => ['title' => 'Zadzwoń']],
            'content_draft' => [['type' => 'hero', 'data' => ['title' => 'Witaj']]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Page::firstWhere('slug', 'nowa-strona'))
        ->fields_draft->toBe(['kontakt' => ['title' => 'Zadzwoń']])
        ->content_draft->toBe([['type' => 'hero', 'data' => ['title' => 'Witaj']]]);
});
