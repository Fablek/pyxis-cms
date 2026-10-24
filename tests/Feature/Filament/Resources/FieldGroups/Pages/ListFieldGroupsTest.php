<?php

use App\Enums\FieldGroupMode;
use App\Filament\Resources\FieldGroups\Pages\ListFieldGroups;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function groupForPage(Page $page, FieldGroupMode $mode = FieldGroupMode::STATIC): FieldGroup
{
    return FieldGroup::factory()->create([
        'mode' => $mode,
        'rules' => [['conditions' => [['param' => 'page', 'operator' => '==', 'value' => $page->id]]]],
    ]);
}

it('shows only groups of the selected usage tab', function (string $tab, FieldGroupMode $visibleMode) {
    $page = Page::factory()->create();
    $block = groupForPage($page, FieldGroupMode::BLOCK);
    $static = groupForPage($page, FieldGroupMode::STATIC);

    [$visible, $hidden] = $visibleMode === FieldGroupMode::BLOCK ? [$block, $static] : [$static, $block];

    Livewire::test(ListFieldGroups::class)
        ->set('activeTab', $tab)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
})->with([
    'content blocks' => ['block', FieldGroupMode::BLOCK],
    'static page fields' => ['static', FieldGroupMode::STATIC],
]);

it('filters groups available on the selected page using their location rules', function () {
    $page = Page::factory()->create();
    $matching = groupForPage($page);
    $otherPage = groupForPage(Page::factory()->create());

    Livewire::test(ListFieldGroups::class)
        ->filterTable('page', $page->id)
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$otherPage]);
});

it('combines the page filter with the usage tab', function () {
    $page = Page::factory()->create();
    $block = groupForPage($page, FieldGroupMode::BLOCK);
    $static = groupForPage($page, FieldGroupMode::STATIC);
    $blockElsewhere = groupForPage(Page::factory()->create(), FieldGroupMode::BLOCK);

    Livewire::test(ListFieldGroups::class)
        ->set('activeTab', 'block')
        ->filterTable('page', $page->id)
        ->assertCanSeeTableRecords([$block])
        ->assertCanNotSeeTableRecords([$static, $blockElsewhere]);
});
