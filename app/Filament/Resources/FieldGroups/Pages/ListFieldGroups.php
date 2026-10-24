<?php

namespace App\Filament\Resources\FieldGroups\Pages;

use App\Enums\FieldGroupMode;
use App\Filament\Resources\FieldGroups\FieldGroupResource;
use App\Models\FieldGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFieldGroups extends ListRecords
{
    protected static string $resource = FieldGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('admin.field_groups.tabs.all'))
                ->badge(FieldGroup::query()->count()),
            'block' => Tab::make(__('admin.field_groups.mode.block'))
                ->badge(FieldGroup::query()->where('mode', FieldGroupMode::BLOCK)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('mode', FieldGroupMode::BLOCK)),
            'static' => Tab::make(__('admin.field_groups.mode.static'))
                ->badge(FieldGroup::query()->where('mode', FieldGroupMode::STATIC)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('mode', FieldGroupMode::STATIC)),
        ];
    }
}
