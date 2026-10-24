<?php

namespace App\Services;

use App\Filament\CustomFields\FieldTypeRegistry;
use App\Models\FieldGroup;
use Illuminate\Support\Collection;

/**
 * Turns saved custom field values into their API shape (e.g. media IDs into URLs),
 * using the definitions of the field groups they belong to.
 */
class FieldValuesPresenter
{
    public function __construct(protected FieldTypeRegistry $registry) {}

    /**
     * Static page fields: {"<group slug>": {"<field>": value}}.
     * Values of groups that no longer exist are dropped.
     *
     * @param  array<string, array<string, mixed>>|null  $fields
     * @return array<string, array<string, mixed>>|null
     */
    public function staticFields(?array $fields): ?array
    {
        if ($fields === null) {
            return null;
        }

        $groups = $this->groupsBySlug(array_keys($fields));

        return collect($fields)
            ->filter(fn (mixed $values, string $slug): bool => $groups->has($slug))
            ->map(fn (mixed $values, string $slug): array => $this->registry->toApiValues(
                $groups->get($slug)->fields ?? [],
                is_array($values) ? $values : [],
            ))
            ->all();
    }

    /**
     * Flexible content: [{"type": "<group slug>", "data": {...}}, ...].
     * Items that are not field group blocks are returned unchanged.
     *
     * @param  array<int, mixed>|null  $content
     * @return array<int, mixed>|null
     */
    public function contentBlocks(?array $content): ?array
    {
        if ($content === null || ! array_is_list($content)) {
            return $content;
        }

        $groups = $this->groupsBySlug(collect($content)->pluck('type')->filter()->all());

        return collect($content)
            ->map(fn (mixed $block): mixed => is_array($block) && $groups->has($block['type'] ?? '')
                ? [
                    'type' => $block['type'],
                    'data' => $this->registry->toApiValues(
                        $groups->get($block['type'])->fields ?? [],
                        is_array($block['data'] ?? null) ? $block['data'] : [],
                    ),
                ]
                : $block)
            ->all();
    }

    /**
     * @param  array<int, string>  $slugs
     * @return Collection<string, FieldGroup>
     */
    protected function groupsBySlug(array $slugs): Collection
    {
        return FieldGroup::query()
            ->whereIn('slug', array_unique($slugs))
            ->get()
            ->keyBy('slug');
    }
}
