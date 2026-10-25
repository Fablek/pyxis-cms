<?php

use App\Plugins\Hook;
use App\Plugins\HookManager;

it('shares one hook manager between the facade and the container', function () {
    Hook::filter('title', fn (string $value): string => strtoupper($value));

    expect(app(HookManager::class)->applyFilters('title', 'pyxis'))->toBe('PYXIS');
});
