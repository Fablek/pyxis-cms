<?php

namespace App\Filament\Resources\FieldGroups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FieldGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('title')
                    ->label(__('admin.field_groups.fields.title'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(__('admin.field_groups.fields.slug'))
                    ->searchable(),
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
            ->filters([
                //
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
