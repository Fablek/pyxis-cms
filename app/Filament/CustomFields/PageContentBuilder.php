<?php

namespace App\Filament\CustomFields;

use App\Enums\FieldGroupMode;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Services\FieldGroupLocator;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Flexible page content: field groups in the "block" mode become blocks
 * the editor can add any number of times, in any order.
 *
 * Stored in "content_draft" as [{"type": "<group slug>", "data": {...}}, ...].
 */
class PageContentBuilder
{
    public static function make(): Builder
    {
        return Builder::make('content_draft')
            ->label(__('admin.pages.fields.content_draft'))
            ->blocks(fn (Get $get, ?Page $record, Builder $component): array => self::blocks(
                PageFieldsForm::pageFromForm($get, $record),
                $component->getRawState() ?? [],
            ))
            ->addActionLabel(__('admin.pages.actions.add_block'))
            ->blockPickerColumns(['default' => 1, 'md' => 2])
            ->collapsible()
            ->cloneable()
            ->columnSpanFull();
    }

    /**
     * Blocks available on the page, plus blocks already used in its content that are
     * no longer available (e.g. after a rule change). Those stay editable, so their
     * content is not lost on save, but maxItems(0) hides them from the block picker.
     *
     * @param  array<array{type?: string}>  $content
     * @return array<Block>
     */
    protected static function blocks(Page $page, array $content): array
    {
        $available = app(FieldGroupLocator::class)->forPage($page, FieldGroupMode::BLOCK);

        $unavailable = FieldGroup::query()
            ->where('mode', FieldGroupMode::BLOCK)
            ->whereIn('slug', collect($content)->pluck('type')->filter()->unique()->diff($available->pluck('slug')))
            ->get();

        return $available->map(fn (FieldGroup $group): Block => self::block($group))
            ->merge($unavailable->map(fn (FieldGroup $group): Block => self::block($group)->maxItems(0)))
            ->all();
    }

    protected static function block(FieldGroup $group): Block
    {
        return Block::make($group->slug)
            ->label($group->title)
            ->columns(['default' => 1, 'lg' => 12])
            ->schema(app(FieldTypeRegistry::class)->formComponents($group->fields ?? []));
    }
}
