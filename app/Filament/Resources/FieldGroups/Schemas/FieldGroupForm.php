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
                        ->placeholder('Tytuł grupy pól')
                        ->required()
                        ->live(onBlur: true)
                        ->extraInputAttributes(['class' => 'text-lg font-medium'])
                        ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create'
                            ? $set('slug', Str::slug($state))
                            : null
                        ),

                    Section::make('Pola')
                        ->afterHeader([
                            Text::make(fn (Get $get): string => self::fieldsCountLabel(count($get('fields') ?? []))),
                        ])
                        ->schema([
                            FieldDefinitionForm::make('fields'),
                        ]),

                    Section::make('Reguły lokalizacji')
                        ->description('Pokaż tę grupę pól, jeśli spełniony jest każdy z warunków.')
                        ->schema([
                            Repeater::make('rules')
                                ->hiddenLabel()
                                ->table([
                                    TableColumn::make('Parametr'),
                                    TableColumn::make('Operator')->width('200px'),
                                    TableColumn::make('Wartość'),
                                ])
                                ->compact()
                                ->addActionLabel('Dodaj warunek')
                                ->schema([
                                    Select::make('param')
                                        ->options([
                                            'page_id' => 'Strona',
                                        ])
                                        ->required()
                                        ->default('page_id')
                                        ->selectablePlaceholder(false),

                                    Select::make('operator')
                                        ->options([
                                            '==' => 'jest równa',
                                            '!=' => 'nie jest równa',
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
                    Section::make('Status')->schema([
                        Toggle::make('is_active')
                            ->label('Aktywna (widoczna)')
                            ->helperText('Nieaktywna grupa nie pojawia się w formularzach stron.')
                            ->default(true),

                        Text::make(fn (?FieldGroup $record): string => 'Ostatnia zmiana: '.$record?->updated_at?->diffForHumans())
                            ->visible(fn (?FieldGroup $record): bool => $record !== null),
                    ]),

                    Section::make('Ustawienia grupy')->schema([
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),
                ])->columnSpan([
                    'sm' => 1,
                    'lg' => 1,
                ]),
            ]);
    }

    protected static function fieldsCountLabel(int $count): string
    {
        return match (true) {
            $count === 1 => '1 pole',
            $count % 10 >= 2 && $count % 10 <= 4 && ($count % 100 < 10 || $count % 100 >= 20) => "{$count} pola",
            default => "{$count} pól",
        };
    }
}
