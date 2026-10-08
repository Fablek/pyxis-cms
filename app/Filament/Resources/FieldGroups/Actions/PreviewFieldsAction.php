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
            ->label('Podgląd formularza')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->modalHeading('Podgląd formularza')
            ->modalDescription('Tak redaktor zobaczy te pola podczas edycji strony. Wartości nie są zapisywane.')
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Zamknij')
            ->schema(function (CreateRecord|EditRecord $livewire): array {
                $components = app(FieldTypeRegistry::class)->formComponents($livewire->data['fields'] ?? []);

                if ($components === []) {
                    return [Text::make('Brak pól do wyświetlenia. Dodaj pole i uzupełnij jego nazwę.')];
                }

                return [
                    Grid::make(['default' => 1, 'lg' => 12])->schema($components),
                ];
            });
    }
}
