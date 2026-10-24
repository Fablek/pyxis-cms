<?php

namespace App\Filament\Resources\FieldGroups\Tables;

use App\Enums\FieldGroupMode;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Services\FieldGroupLocator;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FieldGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.field_groups.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('admin.field_groups.fields.slug'))
                    ->searchable()
                    ->color('gray'),
                TextColumn::make('mode')
                    ->label(__('admin.field_groups.mode.label'))
                    ->badge()
                    ->formatStateUsing(fn (FieldGroupMode $state): string => __("admin.field_groups.mode.{$state->value}"))
                    ->color(fn (FieldGroupMode $state): string => $state === FieldGroupMode::BLOCK ? 'info' : 'gray'),
                TextColumn::make('fields_count')
                    ->label(__('admin.field_groups.fields.fields_column'))
                    ->state(fn (FieldGroup $record): int => count($record->fields ?? [])),
                IconColumn::make('is_active')
                    ->label(__('admin.field_groups.fields.is_active'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('admin.field_groups.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.field_groups.fields.updated_at_column'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('title')
            ->filters([
                SelectFilter::make('page')
                    ->label(__('admin.field_groups.filters.page'))
                    ->options(fn (): array => Page::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        $page = filled($data['value'] ?? null) ? Page::find($data['value']) : null;

                        if ($page === null) {
                            return $query;
                        }

                        $locator = app(FieldGroupLocator::class);

                        $matchingIds = FieldGroup::query()
                            ->get()
                            ->filter(fn (FieldGroup $group): bool => $locator->matches($group, $page))
                            ->modelKeys();

                        return $query->whereKey($matchingIds);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
