<?php

use App\Filament\Pages\ManageSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiSettings;
use Livewire\Livewire;

it('stores the api key encrypted', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageSettings::class)
        ->fillForm(['ai_enabled' => true, 'ai_provider' => 'anthropic', 'ai_api_key' => 'sk-test-key'])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = app(AiSettings::class);

    expect(Setting::get('ai_api_key'))->not->toContain('sk-test-key')
        ->and($settings->apiKey())->toBe('sk-test-key')
        ->and($settings->isEnabled())->toBeTrue();
});

it('keeps the saved api key when the field is left empty', function () {
    $this->actingAs(User::factory()->admin()->create());
    app(AiSettings::class)->storeApiKey('sk-test-key');

    Livewire::test(ManageSettings::class)
        ->fillForm(['ai_enabled' => true, 'ai_provider' => 'openai'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(AiSettings::class)->apiKey())->toBe('sk-test-key');
});

it('does not send the saved api key to the browser', function () {
    $this->actingAs(User::factory()->admin()->create());
    app(AiSettings::class)->storeApiKey('sk-test-key');

    $livewire = Livewire::test(ManageSettings::class)
        ->assertDontSee('sk-test-key');

    expect($livewire->get('data.ai_api_key'))->toBeNull();
});

it('requires a provider and an api key to enable ai', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(ManageSettings::class)
        ->fillForm(['ai_enabled' => true])
        ->call('save')
        ->assertHasFormErrors(['ai_provider' => 'required', 'ai_api_key' => 'required']);
});

it('does not let an editor change the ai settings', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSettings::class)
        ->assertDontSee(__('admin.settings.fields.ai_api_key'))
        ->set('data.ai_provider', 'openai')
        ->set('data.ai_api_key', 'sk-injected')
        ->call('save');

    expect(Setting::get('ai_provider'))->toBeNull()
        ->and(app(AiSettings::class)->hasApiKey())->toBeFalse();
});
