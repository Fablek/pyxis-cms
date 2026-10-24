<?php

use App\Enums\FieldGroupMode;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\Setting;
use App\Services\FieldGroupLocator;

function condition(string $param, string $value, string $operator = '=='): array
{
    return ['param' => $param, 'operator' => $operator, 'value' => $value];
}

function groupWithRules(array ...$ruleGroups): FieldGroup
{
    return FieldGroup::factory()->create([
        'rules' => array_map(fn (array $conditions): array => ['conditions' => $conditions], $ruleGroups),
    ]);
}

it('matches when all conditions of a rule group match', function () {
    $parent = Page::factory()->create();
    $page = Page::factory()->create(['parent_id' => $parent->id]);

    $group = groupWithRules([
        condition('page_parent', $parent->id),
        condition('page_type', 'child'),
    ]);

    expect(app(FieldGroupLocator::class)->matches($group, $page))->toBeTrue();
});

it('does not match when one condition of a rule group fails', function () {
    $parent = Page::factory()->create();
    $page = Page::factory()->create(['parent_id' => $parent->id]);

    $group = groupWithRules([
        condition('page_parent', $parent->id),
        condition('page_type', 'top_level'),
    ]);

    expect(app(FieldGroupLocator::class)->matches($group, $page))->toBeFalse();
});

it('matches when any rule group matches', function () {
    $page = Page::factory()->create();
    $otherPage = Page::factory()->create();

    $group = groupWithRules(
        [condition('page', $otherPage->id)],
        [condition('page', $page->id)],
    );

    expect(app(FieldGroupLocator::class)->matches($group, $page))->toBeTrue();
});

it('negates a condition with the not equal operator', function () {
    $page = Page::factory()->create();
    $otherPage = Page::factory()->create();

    $locator = app(FieldGroupLocator::class);

    expect($locator->matches(groupWithRules([condition('page', $otherPage->id, '!=')]), $page))->toBeTrue()
        ->and($locator->matches(groupWithRules([condition('page', $page->id, '!=')]), $page))->toBeFalse();
});

it('recognizes the page type', function (string $type, Closure $makePage, bool $expected) {
    $page = $makePage();

    expect(app(FieldGroupLocator::class)->matches(groupWithRules([condition('page_type', $type)]), $page))
        ->toBe($expected);
})->with([
    'homepage' => ['homepage', function () {
        $page = Page::factory()->create(['slug' => null]);
        Setting::set('homepage_id', $page->id);

        return $page;
    }, true],
    'not the homepage' => ['homepage', fn () => Page::factory()->create(), false],
    'top level' => ['top_level', fn () => Page::factory()->create(), true],
    'parent with children' => ['parent', function () {
        $page = Page::factory()->create();
        Page::factory()->create(['parent_id' => $page->id]);

        return $page;
    }, true],
    'parent without children' => ['parent', fn () => Page::factory()->create(), false],
    'child' => ['child', fn () => Page::factory()->create(['parent_id' => Page::factory()->create()->id]), true],
    'unsaved child page' => ['child', fn () => Page::factory()->make(['parent_id' => Page::factory()->create()->id]), true],
]);

it('does not match unknown parameters or groups without rules', function () {
    $page = Page::factory()->create();
    $locator = app(FieldGroupLocator::class);

    expect($locator->matches(groupWithRules([condition('unknown', $page->id)]), $page))->toBeFalse()
        ->and($locator->matches(FieldGroup::factory()->create(['rules' => []]), $page))->toBeFalse()
        ->and($locator->matches(groupWithRules([]), $page))->toBeFalse();
});

it('returns only active matching groups of the given mode for a page', function () {
    $page = Page::factory()->create();

    $matching = groupWithRules([condition('page', $page->id)]);
    $inactive = groupWithRules([condition('page', $page->id)]);
    $inactive->update(['is_active' => false]);
    $block = groupWithRules([condition('page', $page->id)]);
    $block->update(['mode' => FieldGroupMode::BLOCK]);
    groupWithRules([condition('page', Page::factory()->create()->id)]);

    expect(app(FieldGroupLocator::class)->forPage($page, FieldGroupMode::STATIC)->modelKeys())->toBe([$matching->id])
        ->and(app(FieldGroupLocator::class)->forPage($page, FieldGroupMode::BLOCK)->modelKeys())->toBe([$block->id]);
});
