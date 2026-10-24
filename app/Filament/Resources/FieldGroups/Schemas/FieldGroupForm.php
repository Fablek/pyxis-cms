<?php

namespace App\Filament\Resources\FieldGroups\Schemas;

use App\Enums\FieldGroupMode;
use App\Filament\CustomFields\FieldDefinitionForm;
use App\Models\FieldGroup;
use App\Models\Page;
use App\Services\FieldGroupLocator;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FieldGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'sm' => 1,
                'lg' => 3,
            ])
            ->components([
                Group::make([
                    TextInput::make('title')
                        ->hiddenLabel()
                        ->placeholder(__('admin.field_groups.fields.title_placeholder'))
                        ->required()
                        ->live(onBlur: true)
                        ->extraInputAttributes(['class' => 'text-lg font-medium'])
                        ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create'
                            ? $set('slug', Str::slug($state))
                            : null
                        ),

                    Section::make(__('admin.field_groups.sections.fields'))
                        ->afterHeader([
                            Text::make(fn (Get $get): string => trans_choice('admin.field_groups.fields_count', count($get('fields') ?? []))),
                        ])
                        ->schema([
                            FieldDefinitionForm::make('fields'),
                        ]),

                    Section::make(__('admin.field_groups.sections.location'))
                        ->description(__('admin.field_groups.sections.location_desc'))
                        ->schema([
                            self::locationRules(),
                        ]),
                ])->columnSpan([
                    'sm' => 1,
                    'lg' => 2,
                ]),

                Group::make([
                    Section::make(__('admin.field_groups.sections.status'))->schema([
                        Radio::make('mode')
                            ->label(__('admin.field_groups.mode.label'))
                            ->options([
                                FieldGroupMode::STATIC->value => __('admin.field_groups.mode.static'),
                                FieldGroupMode::BLOCK->value => __('admin.field_groups.mode.block'),
                            ])
                            ->descriptions([
                                FieldGroupMode::STATIC->value => __('admin.field_groups.mode.static_help'),
                                FieldGroupMode::BLOCK->value => __('admin.field_groups.mode.block_help'),
                            ])
                            ->default(FieldGroupMode::STATIC->value)
                            ->required(),

                        Toggle::make('is_active')
                            ->label(__('admin.field_groups.fields.is_active'))
                            ->helperText(__('admin.field_groups.fields.is_active_help'))
                            ->default(true),

                        Text::make(fn (?FieldGroup $record): string => __('admin.field_groups.fields.updated_at', ['time' => $record?->updated_at?->diffForHumans()]))
                            ->visible(fn (?FieldGroup $record): bool => $record !== null),
                    ]),

                    Section::make(__('admin.field_groups.sections.settings'))->schema([
                        TextInput::make('slug')
                            ->label(__('admin.field_groups.fields.slug'))
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),
                ])->columnSpan([
                    'sm' => 1,
                    'lg' => 1,
                ]),
            ]);
    }

    /**
     * ACF-like location rules: groups joined with "or", conditions inside a group joined with "and".
     */
    protected static function locationRules(): Repeater
    {
        return Repeater::make('rules')
            ->hiddenLabel()
            ->itemLabel(function (string $key, Repeater $component): string {
                $isFirstGroup = (string) array_key_first($component->getRawState() ?? []) === $key;

                return __($isFirstGroup ? 'admin.field_groups.location.show_if' : 'admin.field_groups.location.or');
            })
            ->addActionLabel(__('admin.field_groups.location.add_group'))
            ->defaultItems(0)
            ->schema([
                Repeater::make('conditions')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make(__('admin.field_groups.location.param')),
                        TableColumn::make(__('admin.field_groups.location.operator'))->width('180px'),
                        TableColumn::make(__('admin.field_groups.location.value')),
                    ])
                    ->compact()
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel(__('admin.field_groups.location.add_condition'))
                    ->schema([
                        Select::make('param')
                            ->options([
                                FieldGroupLocator::PARAM_PAGE => __('admin.field_groups.location.params.page'),
                                FieldGroupLocator::PARAM_PAGE_PARENT => __('admin.field_groups.location.params.page_parent'),
                                FieldGroupLocator::PARAM_PAGE_TYPE => __('admin.field_groups.location.params.page_type'),
                            ])
                            ->default(FieldGroupLocator::PARAM_PAGE)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('value', null))
                            ->selectablePlaceholder(false),

                        Select::make('operator')
                            ->options([
                                FieldGroupLocator::OPERATOR_EQUALS => __('admin.field_groups.location.operators.equals'),
                                FieldGroupLocator::OPERATOR_NOT_EQUALS => __('admin.field_groups.location.operators.not_equals'),
                            ])
                            ->default(FieldGroupLocator::OPERATOR_EQUALS)
                            ->required()
                            ->selectablePlaceholder(false),

                        Select::make('value')
                            ->required()
                            ->searchable()
                            ->options(fn (Get $get): array => $get('param') === FieldGroupLocator::PARAM_PAGE_TYPE
                                ? [
                                    FieldGroupLocator::TYPE_HOMEPAGE => __('admin.field_groups.location.page_types.homepage'),
                                    FieldGroupLocator::TYPE_TOP_LEVEL => __('admin.field_groups.location.page_types.top_level'),
                                    FieldGroupLocator::TYPE_PARENT => __('admin.field_groups.location.page_types.parent'),
                                    FieldGroupLocator::TYPE_CHILD => __('admin.field_groups.location.page_types.child'),
                                ]
                                : Page::query()->orderBy('title')->pluck('title', 'id')->all()),
                    ]),
            ]);
    }
}
