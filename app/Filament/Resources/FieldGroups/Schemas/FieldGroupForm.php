<?php

namespace App\Filament\Resources\FieldGroups\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
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
                    Section::make('Podstawowe informacje')->schema([
                        TextInput::make('title')
                            ->label('Tytuł grupy')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create'
                                ? $set('slug', Str::slug($state))
                                : null
                            ),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                    Section::make('Definicje Pól')
                        ->description('Zdefiniuj pola, które pojawią się w formularzu edycji strony.')
                        ->schema([
                            Repeater::make('fields')
                                ->label('')
                                ->addActionLabel('Dodaj nowe pole')
                                ->collapsible()
                                ->reorderable()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Nowe pole')
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Etykieta (Label)')
                                        ->placeholder('np. Nagłówek sekcji')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, $set) => $set('name', Str::snake($state))),

                                    TextInput::make('name')
                                        ->label('Nazwa systemowa (Name / klucza)')
                                        ->placeholder('np. naglowek_sekcji')
                                        ->required()
                                        ->regex('/^[a-z0-9_]+$/')
                                        ->validationMessages([
                                            'regex' => 'Nazwa może zawierać tylko małe litery, cyfry i podkreślenia.',
                                        ]),

                                    Select::make('type')
                                        ->label('Typ pola')
                                        ->options([
                                            'text' => 'Tekst (Text)',
                                            'textarea' => 'Pole tekstowe (Textarea)',
                                            'wysiwyg' => 'Edytor WYSIWYG',
                                            'media' => 'Obraz / Media',
                                            'toggle' => 'Przełącznik (Tak/Nie)',
                                        ])
                                        ->required()
                                        ->default('text'),

                                    Toggle::make('is_required')
                                        ->label('Wymagane?')
                                        ->default(false),
                                ])->columns(2),
                        ]),

                    Section::make('Warunki wyświetlania (Location Rules)')
                        ->description('Określ, gdzie ta grupa pól ma być dostępna.')
                        ->schema([
                            Repeater::make('rules')
                                ->label('')
                                ->addActionLabel('Dodaj warunek')
                                ->schema([
                                    Select::make('param')
                                        ->label('Parametr')
                                        ->options([
                                            'template' => 'Szablon strony (Template)',
                                            'page_id' => 'Strona (Konkretne ID)',
                                        ])
                                        ->required(),

                                    Select::make('operator')
                                        ->label('Operator')
                                        ->options([
                                            '==' => 'równa się (==)',
                                            '!=' => 'nie równa się (!=)',
                                        ])
                                        ->required()
                                        ->default('=='),

                                    TextInput::make('value')
                                        ->label('Wartość')
                                        ->placeholder('np. contact, albo UUID strony')
                                        ->required(),
                                ])->columns(3),
                        ]),

                ])->columnSpan([
                    'sm' => 1,
                    'lg' => 2,
                ]),

                Group::make([
                    Section::make('Status')->schema([
                        Toggle::make('is_active')
                            ->label('Aktywna (widoczna)')
                            ->default(true),
                    ]),
                ])->columnSpan([
                    'sm' => 1,
                    'lg' => 1,
                ]),
            ]);
    }
}
