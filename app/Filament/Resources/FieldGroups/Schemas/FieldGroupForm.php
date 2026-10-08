<?php

namespace App\Filament\Resources\FieldGroups\Schemas;

use App\Filament\CustomFields\FieldDefinitionForm;
use App\Models\FieldGroup;
use App\Models\Page;
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
                            Repeater::make('rules')
                                ->hiddenLabel()
                                ->table([
                                    TableColumn::make(__('admin.field_groups.location.param')),
                                    TableColumn::make(__('admin.field_groups.location.operator'))->width('200px'),
                                    TableColumn::make(__('admin.field_groups.location.value')),
                                ])
                                ->compact()
                                ->addActionLabel(__('admin.field_groups.location.add'))
                                ->schema([
                                    Select::make('param')
                                        ->options([
                                            'page_id' => __('admin.field_groups.location.params.page_id'),
                                        ])
                                        ->required()
                                        ->default('page_id')
                                        ->selectablePlaceholder(false),

                                    Select::make('operator')
                                        ->options([
                                            '==' => __('admin.field_groups.location.operators.equals'),
                                            '!=' => __('admin.field_groups.location.operators.not_equals'),
                                        ])
                                        ->required()
                                        ->default('==')
                                        ->selectablePlaceholder(false),

                                    Select::make('value')
                                        ->required()
                                        ->searchable()
                                        ->options(fn () => Page::pluck('title', 'id')->toArray()),
                                ]),
                        ]),
                ])->columnSpan([
                    'sm' => 1,
                    'lg' => 2,
                ]),

                Group::make([
                    Section::make(__('admin.field_groups.sections.status'))->schema([
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
}
