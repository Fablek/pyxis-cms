<?php

namespace App\Filament\CustomFields;

use App\Models\FieldGroup;
use App\Models\Page;
use App\Services\FieldGroupLocator;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Field groups matching the edited page, rendered in the page form.
 *
 * Values are stored in "fields_draft", keyed by the group slug: {"hero": {"title": "..."}}.
 */
class PageFieldsForm
{
    public static function make(): Group
    {
        return Group::make()
            ->statePath('fields_draft')
            ->columnSpanFull()
            ->schema(fn (Get $get, ?Page $record): array => app(FieldGroupLocator::class)
                ->forPage(self::pageFromForm($get, $record))
                ->map(fn (FieldGroup $group): Section => Section::make($group->title)
                    ->statePath($group->slug)
                    ->columns(['default' => 1, 'lg' => 12])
                    ->schema(app(FieldTypeRegistry::class)->formComponents($group->fields ?? [])))
                ->all());
    }

    /**
     * The page as currently edited, so location rules react to unsaved changes (e.g. a new parent).
     */
    protected static function pageFromForm(Get $get, ?Page $record): Page
    {
        $page = $record ? clone $record : new Page;
        $page->parent_id = $get('parent_id');

        return $page;
    }
}
