<?php

namespace App\Plugins;

/**
 * WordPress-like hooks: actions react to events, filters modify a value passed through them.
 *
 * Callbacks run by priority (lower first, default 10), then in registration order.
 */
class HookManager
{
    /**
     * @var array<string, array<int, array<int, callable>>>
     */
    protected array $actions = [];

    /**
     * @var array<string, array<int, array<int, callable>>>
     */
    protected array $filters = [];

    public function action(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][$priority][] = $callback;
    }

    public function filter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][$priority][] = $callback;
    }

    public function doAction(string $hook, mixed ...$args): void
    {
        foreach ($this->callbacks($this->actions, $hook) as $callback) {
            $callback(...$args);
        }
    }

    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        foreach ($this->callbacks($this->filters, $hook) as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }

    /**
     * @param  array<string, array<int, array<int, callable>>>  $registry
     * @return array<int, callable>
     */
    protected function callbacks(array $registry, string $hook): array
    {
        $byPriority = $registry[$hook] ?? [];
        ksort($byPriority);

        return array_merge(...array_values($byPriority));
    }
}
