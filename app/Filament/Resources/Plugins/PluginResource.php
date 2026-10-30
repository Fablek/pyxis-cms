<?php

namespace App\Filament\Resources\Plugins;

use App\Filament\Resources\Plugins\Pages\ManagePlugins;
use App\Filament\Resources\Plugins\Tables\PluginsTable;
use App\Models\Plugin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PluginResource extends Resource
{
    protected static ?string $model = Plugin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return PluginsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlugins::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.plugins.nav_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.plugins.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.plugins.plural_label');
    }
}
