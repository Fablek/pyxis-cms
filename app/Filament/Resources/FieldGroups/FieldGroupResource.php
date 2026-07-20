<?php

namespace App\Filament\Resources\FieldGroups;

use App\Filament\Resources\FieldGroups\Pages\CreateFieldGroup;
use App\Filament\Resources\FieldGroups\Pages\EditFieldGroup;
use App\Filament\Resources\FieldGroups\Pages\ListFieldGroups;
use App\Filament\Resources\FieldGroups\Schemas\FieldGroupForm;
use App\Filament\Resources\FieldGroups\Tables\FieldGroupsTable;
use App\Models\FieldGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FieldGroupResource extends Resource
{
    protected static ?string $model = FieldGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return FieldGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FieldGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFieldGroups::route('/'),
            'create' => CreateFieldGroup::route('/create'),
            'edit' => EditFieldGroup::route('/{record}/edit'),
        ];
    }
}
