<?php

namespace App\Filament\Resources\FieldGroups\Actions;

use App\Filament\CustomFields\FieldTypeRegistry;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Renders the (unsaved) field definitions exactly as an editor will see them.
 */
class PreviewFieldsAction
{
    public static function make(): Action
    {
        return Action::make('previewFields')
            ->label(__('admin.field_groups.preview.label'))
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->modalHeading(__('admin.field_groups.preview.label'))
            ->modalDescription(__('admin.field_groups.preview.description'))
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('admin.field_groups.preview.close'))
            ->schema(function (CreateRecord|EditRecord $livewire): array {
                $components = app(FieldTypeRegistry::class)->formComponents($livewire->data['fields'] ?? []);

                if ($components === []) {
                    return [Text::make(__('admin.field_groups.preview.empty'))];
                }

                return [
                    Grid::make(['default' => 1, 'lg' => 12])->schema($components),
                ];
            });
    }
}
