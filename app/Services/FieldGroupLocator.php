<?php

namespace App\Services;

use App\Enums\FieldGroupMode;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * Decides which field groups are available for a page, based on their location rules.
 *
 * Rules work like in ACF: a group is shown when ALL conditions of ANY rule group match.
 * Stored format: [{"conditions": [{"param": "page", "operator": "==", "value": "<uuid>"}, ...]}, ...]
 */
class FieldGroupLocator
{
    public const string PARAM_PAGE = 'page';

    public const string PARAM_PAGE_PARENT = 'page_parent';

    public const string PARAM_PAGE_TYPE = 'page_type';

    public const string OPERATOR_EQUALS = '==';

    public const string OPERATOR_NOT_EQUALS = '!=';

    public const string TYPE_HOMEPAGE = 'homepage';

    public const string TYPE_TOP_LEVEL = 'top_level';

    public const string TYPE_PARENT = 'parent';

    public const string TYPE_CHILD = 'child';

    /**
     * Active field groups whose location rules match the page (which may be unsaved).
     *
     * @return Collection<int, FieldGroup>
     */
    public function forPage(Page $page, FieldGroupMode $mode): Collection
    {
        return FieldGroup::query()
            ->where('is_active', true)
            ->where('mode', $mode)
            ->orderBy('title')
            ->get()
            ->filter(fn (FieldGroup $group): bool => $this->matches($group, $page))
            ->values();
    }

    /**
     * A group without rules is not shown anywhere.
     */
    public function matches(FieldGroup $group, Page $page): bool
    {
        return collect($group->rules ?? [])->contains(function (mixed $ruleGroup) use ($page): bool {
            $conditions = is_array($ruleGroup) ? ($ruleGroup['conditions'] ?? []) : [];

            return $conditions !== [] && collect($conditions)->every(
                fn (mixed $condition): bool => is_array($condition) && $this->conditionMatches($condition, $page)
            );
        });
    }

    /**
     * @param  array{param?: string, operator?: string, value?: string|null}  $condition
     */
    protected function conditionMatches(array $condition, Page $page): bool
    {
        $value = (string) ($condition['value'] ?? '');

        $isEqual = match ($condition['param'] ?? null) {
            self::PARAM_PAGE => $page->exists && (string) $page->getKey() === $value,
            self::PARAM_PAGE_PARENT => $page->parent_id !== null && (string) $page->parent_id === $value,
            self::PARAM_PAGE_TYPE => $this->isPageOfType($page, $value),
            default => null,
        };

        if ($isEqual === null) {
            return false;
        }

        return ($condition['operator'] ?? self::OPERATOR_EQUALS) === self::OPERATOR_NOT_EQUALS
            ? ! $isEqual
            : $isEqual;
    }

    protected function isPageOfType(Page $page, string $type): bool
    {
        return match ($type) {
            self::TYPE_HOMEPAGE => $page->exists && (string) $page->getKey() === (string) Setting::get('homepage_id'),
            self::TYPE_TOP_LEVEL => $page->parent_id === null,
            self::TYPE_PARENT => $page->exists && $page->children()->exists(),
            self::TYPE_CHILD => $page->parent_id !== null,
            default => false,
        };
    }
}
