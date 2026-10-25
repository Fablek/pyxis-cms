<?php

use App\Plugins\HookManager;

it('passes a value through filters by priority, then in registration order', function () {
    $hooks = new HookManager;
    $hooks->filter('title', fn (string $value): string => $value.'B', priority: 20);
    $hooks->filter('title', fn (string $value): string => $value.'A');
    $hooks->filter('title', fn (string $value): string => $value.'C', priority: 20);

    expect($hooks->applyFilters('title', '-'))->toBe('-ABC');
});

it('returns the value unchanged when no filter is registered', function () {
    expect((new HookManager)->applyFilters('unknown', 'value'))->toBe('value');
});

it('passes extra arguments to filters', function () {
    $hooks = new HookManager;
    $hooks->filter('greeting', fn (string $value, string $name): string => "{$value}, {$name}");

    expect($hooks->applyFilters('greeting', 'Hello', 'Pyxis'))->toBe('Hello, Pyxis');
});

it('runs actions by priority with their arguments', function () {
    $hooks = new HookManager;
    $calls = [];
    $hooks->action('saved', function (string $title) use (&$calls): void {
        $calls[] = "second:{$title}";
    }, priority: 20);
    $hooks->action('saved', function (string $title) use (&$calls): void {
        $calls[] = "first:{$title}";
    }, priority: 5);

    $hooks->doAction('saved', 'Home');
    $hooks->doAction('unknown');

    expect($calls)->toBe(['first:Home', 'second:Home']);
});
