<?php

namespace App\Plugins;

use Illuminate\Support\Facades\Facade;

/**
 * Static access to the hook manager, used by plugins and the core.
 *
 * @method static void action(string $hook, callable $callback, int $priority = 10)
 * @method static void filter(string $hook, callable $callback, int $priority = 10)
 * @method static void doAction(string $hook, mixed ...$args)
 * @method static mixed applyFilters(string $hook, mixed $value, mixed ...$args)
 *
 * @see HookManager
 */
class Hook extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HookManager::class;
    }
}
