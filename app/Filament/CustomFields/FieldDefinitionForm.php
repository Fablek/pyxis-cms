<?php

namespace App\Filament\CustomFields;

use App\Filament\CustomFields\FieldTypes\FieldType;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * ACF-like table of field definitions: one collapsed row per field
 * (number, label, name, type) that expands into tabbed settings.
 */
class FieldDefinitionForm
{
    public static function make(string $name, int $depth = 0): Repeater
    {
        $registry = app(FieldTypeRegistry::class);

        return Repeater::make($name)
            ->hiddenLabel($depth === 0)
            ->extraAttributes(['class' => 'pyxis-fields-table'])
            ->schema([
                Tabs::make()
                    ->contained(false)
                    ->extraAttributes(['class' => 'pyxis-tabs'])
                    ->tabs([
                        Tab::make(__('admin.custom_fields.tabs.general'))
                            ->schema(fn (Get $get): array => [
                                Select::make('type')
                                    ->label(__('admin.custom_fields.definition.type'))
                                    ->options($registry->options($depth))
                                    ->default('text')
                                    ->required()
                                    ->live()
                                    ->selectablePlaceholder(false),

                                TextInput::make('label')
                                    ->label(__('admin.custom_fields.definition.label'))
                                    ->placeholder(__('admin.custom_fields.definition.label_placeholder'))
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                        if (filled($state) && blank($get('name'))) {
                                            $set('name', Str::slug($state, '_'));
                                        }
                                    }),

                                TextInput::make('name')
                                    ->label(__('admin.custom_fields.definition.name'))
                                    ->placeholder(__('admin.custom_fields.definition.name_placeholder'))
                                    ->helperText(__('admin.custom_fields.definition.name_help'))
                                    ->required()
                                    ->live(onBlur: true)
                                    ->regex('/^[a-z][a-z0-9_]*$/')
                                    ->validationMessages([
                                        'regex' => __('admin.custom_fields.definition.name_regex'),
                                    ]),

                                ...self::typeOf($get)->generalSettings($depth),
                            ])
                            ->columns(['default' => 1, 'md' => 2]),

                        Tab::make(__('admin.custom_fields.tabs.validation'))
                            ->schema(fn (Get $get): array => [
                                Toggle::make('required')
                                    ->label(__('admin.custom_fields.definition.required'))
                                    ->visible(self::typeOf($get)->supportsRequired())
                                    ->columnSpanFull(),

                                ...self::typeOf($get)->validationSettings(),
                            ])
                            ->visible(fn (Get $get): bool => self::typeOf($get)->supportsRequired()
                                || self::typeOf($get)->validationSettings() !== [])
                            ->columns(['default' => 1, 'md' => 2]),

                        Tab::make(__('admin.custom_fields.tabs.presentation'))
                            ->schema(fn (Get $get): array => [
                                Textarea::make('instructions')
                                    ->label(__('admin.custom_fields.definition.instructions'))
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Select::make('width')
                                    ->label(__('admin.custom_fields.definition.width'))
                                    ->options([
                                        'full' => '100%',
                                        'three_quarters' => '75%',
                                        'two_thirds' => '66%',
                                        'half' => '50%',
                                        'third' => '33%',
                                        'quarter' => '25%',
                                    ])
                                    ->default('full')
                                    ->selectablePlaceholder(false),

                                ...self::typeOf($get)->presentationSettings(),
                            ])
                            ->columns(['default' => 1, 'md' => 2]),
                    ]),
            ])
            ->itemLabel(fn (array $state): HtmlString => new HtmlString(
                view('filament.custom-fields.field-row', [
                    'label' => $state['label'] ?? null,
                    'name' => $state['name'] ?? null,
                    'isRequired' => (bool) ($state['required'] ?? false),
                    'type' => $registry->has($state['type'] ?? '') ? $registry->get($state['type']) : null,
                ])->render()
            ))
            ->collapsible()
            ->collapsed()
            ->collapseAllAction(fn (Action $action): Action => $action->hidden())
            ->expandAllAction(fn (Action $action): Action => $action->hidden())
            ->cloneable()
            ->reorderable()
            ->addActionLabel(__('admin.custom_fields.definition.add'))
            ->addActionAlignment(Alignment::End)
            ->defaultItems(0)
            ->rules([self::uniqueNamesRule()]);
    }

    protected static function typeOf(Get $get): FieldType
    {
        $registry = app(FieldTypeRegistry::class);

        return $registry->get($registry->has($get('type') ?? '') ? $get('type') : 'text');
    }

    /**
     * Field names must be unique on one level, otherwise their values would overwrite each other.
     */
    protected static function uniqueNamesRule(): Closure
    {
        return fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
            $duplicates = collect($value)
                ->pluck('name')
                ->filter()
                ->duplicates()
                ->unique();

            if ($duplicates->isNotEmpty()) {
                $fail(__('admin.custom_fields.definition.unique_names', ['names' => $duplicates->implode(', ')]));
            }
        };
    }
}
