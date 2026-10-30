<?php

use App\Models\Page;
use App\Plugins\Hook;
use App\Plugins\HookManager;

it('shares one hook manager between the facade and the container', function () {
    Hook::filter('title', fn (string $value): string => strtoupper($value));

    expect(app(HookManager::class)->applyFilters('title', 'pyxis'))->toBe('PYXIS');
});

it('runs the page.saved action whenever a page is saved', function () {
    $saved = [];
    Hook::action('page.saved', function (Page $page) use (&$saved): void {
        $saved[] = $page->title;
    });

    $page = Page::factory()->create(['title' => 'Nowa']);
    $page->update(['title' => 'Zmieniona']);

    expect($saved)->toBe(['Nowa', 'Zmieniona']);
});
