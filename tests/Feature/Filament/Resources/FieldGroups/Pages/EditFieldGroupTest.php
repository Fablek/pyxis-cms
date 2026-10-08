<?php

use App\Filament\CustomFields\FieldTypeRegistry;
use App\Filament\Resources\FieldGroups\Pages\EditFieldGroup;
use App\Models\FieldGroup;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    Repeater::fake();
});

function textField(string $name, string $label = 'Pole'): array
{
    return ['type' => 'text', 'label' => $label, 'name' => $name, 'width' => 'full'];
}

it('saves nested field definitions', function () {
    $group = FieldGroup::factory()->create();

    Livewire::test(EditFieldGroup::class, ['record' => $group->getRouteKey()])
        ->fillForm([
            'fields' => [
                [
                    'type' => 'repeater',
                    'label' => 'FAQ',
                    'name' => 'faq',
                    'width' => 'full',
                    'sub_fields' => [textField('question', 'Pytanie')],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fields = $group->fresh()->fields;

    expect($fields[0]['type'])->toBe('repeater')
        ->and($fields[0]['name'])->toBe('faq')
        ->and($fields[0]['sub_fields'][0]['name'])->toBe('question');
});

it('rejects field definitions with duplicate names', function () {
    $group = FieldGroup::factory()->create();

    Livewire::test(EditFieldGroup::class, ['record' => $group->getRouteKey()])
        ->fillForm([
            'fields' => [textField('title'), textField('title')],
        ])
        ->call('save')
        ->assertHasFormErrors(['fields']);

    expect($group->fresh()->fields)->toBe([]);
});

it('generates the system name from the label only while the name is empty', function () {
    $group = FieldGroup::factory()->create([
        'fields' => [textField(''), textField('custom_name')],
    ]);

    Livewire::test(EditFieldGroup::class, ['record' => $group->getRouteKey()])
        ->set('data.fields.0.label', 'Nagłówek sekcji')
        ->set('data.fields.1.label', 'Inna etykieta')
        ->assertSet('data.fields.0.name', 'naglowek_sekcji')
        ->assertSet('data.fields.1.name', 'custom_name');
});

it('opens the form preview for unsaved field definitions', function () {
    $group = FieldGroup::factory()->create();

    Livewire::test(EditFieldGroup::class, ['record' => $group->getRouteKey()])
        ->fillForm(['fields' => [textField('headline')]])
        ->mountAction('previewFields')
        ->assertActionMounted('previewFields');
});

it('builds form components from field definitions and skips incomplete ones', function () {
    $components = app(FieldTypeRegistry::class)->formComponents([
        ['type' => 'text', 'label' => 'Nagłówek', 'name' => 'headline', 'required' => true],
        ['type' => 'text', 'label' => 'Bez nazwy', 'name' => ''],
        ['type' => 'unknown', 'label' => 'Nieznany', 'name' => 'unknown'],
    ]);

    expect($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(TextInput::class)
        ->and($components[0]->getName())->toBe('headline')
        ->and($components[0]->isRequired())->toBeTrue();
});

it('does not offer nested field types beyond the maximum depth', function () {
    $registry = app(FieldTypeRegistry::class);

    expect($registry->options(0))->toHaveKeys(['repeater', 'group'])
        ->and($registry->options(FieldTypeRegistry::MAX_DEPTH))->not->toHaveKey('repeater')
        ->and($registry->options(FieldTypeRegistry::MAX_DEPTH))->not->toHaveKey('group')
        ->and($registry->options(FieldTypeRegistry::MAX_DEPTH))->toHaveKey('text');
});
