<?php

use App\Filament\CustomFields\FieldTypeRegistry;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('returns an image as an object with its url and description', function () {
    $image = storedImage('tlo', 'Biuro');

    $values = app(FieldTypeRegistry::class)->toApiValues(
        [['type' => 'image', 'name' => 'background']],
        ['background' => $image->id],
    );

    expect($values['background'])->toBe([
        'id' => $image->id,
        'url' => '/storage/media/tlo.jpg',
        'alt' => 'Biuro',
        'title' => null,
        'width' => 1920,
        'height' => 1080,
    ]);
});

it('returns gallery images in the saved order and skips missing or invalid ones', function () {
    $first = storedImage('pierwszy', 'Pierwszy');
    $second = storedImage('drugi', 'Drugi');
    $deleted = storedImage('usuniety', 'Usunięty');
    $deleted->delete();

    $values = app(FieldTypeRegistry::class)->toApiValues(
        [['type' => 'gallery', 'name' => 'photos']],
        ['photos' => [$second->id, 'not-a-uuid', $deleted->id, $first->id]],
    );

    expect(array_column($values['photos'], 'alt'))->toBe(['Drugi', 'Pierwszy']);
});

it('transforms values inside repeaters and groups', function () {
    $image = storedImage('tlo', 'Biuro');
    $imageField = [['type' => 'image', 'name' => 'image']];

    $values = app(FieldTypeRegistry::class)->toApiValues(
        [
            ['type' => 'repeater', 'name' => 'slides', 'sub_fields' => $imageField],
            ['type' => 'group', 'name' => 'hero', 'sub_fields' => $imageField],
        ],
        [
            'slides' => ['item-uuid' => ['image' => $image->id]],
            'hero' => ['image' => $image->id],
        ],
    );

    expect($values['slides'][0]['image']['alt'])->toBe('Biuro')
        ->and($values['hero']['image']['alt'])->toBe('Biuro');
});
