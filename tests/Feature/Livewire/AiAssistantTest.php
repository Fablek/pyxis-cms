<?php

use App\Ai\Agents\PageAssistant;
use App\Filament\Resources\PageResource;
use App\Livewire\AiAssistant;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiSettings;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Prompts\AgentPrompt;
use Livewire\Livewire;

function enableAiAssistant(): void
{
    Setting::set('ai_enabled', true);
    Setting::set('ai_provider', 'anthropic');
    Setting::set('ai_model', 'test-model');
    app(AiSettings::class)->storeApiKey('sk-test-key');
}

it('shows the assistant on the page form when ai is enabled', function () {
    enableAiAssistant();
    $this->withoutVite()->actingAs(User::factory()->create());

    $this->get(PageResource::getUrl('edit', ['record' => Page::factory()->create()]))
        ->assertSeeLivewire(AiAssistant::class)
        ->assertSee("\$dispatch('ai-assistant-toggle')", false);
});

it('does not show the assistant outside the page form', function () {
    enableAiAssistant();
    $this->withoutVite()->actingAs(User::factory()->create());

    $this->get(PageResource::getUrl('index'))
        ->assertDontSeeLivewire(AiAssistant::class)
        ->assertDontSee("\$dispatch('ai-assistant-toggle')", false);
});

it('does not show the assistant when ai is disabled', function () {
    $this->withoutVite()->actingAs(User::factory()->create());

    $this->get(PageResource::getUrl('edit', ['record' => Page::factory()->create()]))
        ->assertDontSeeLivewire(AiAssistant::class)
        ->assertDontSee("\$dispatch('ai-assistant-toggle')", false);
});

it('replies and sends the earlier messages to the provider', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake(['Pierwsza odpowiedź', 'Druga odpowiedź']);

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Pierwsze pytanie')
        ->call('send')
        ->assertSet('prompt', '')
        ->call('reply')
        ->set('prompt', 'Drugie pytanie')
        ->call('send')
        ->call('reply')
        ->assertSet('messages', [
            ['role' => 'user', 'content' => 'Pierwsze pytanie'],
            ['role' => 'assistant', 'content' => 'Pierwsza odpowiedź'],
            ['role' => 'user', 'content' => 'Drugie pytanie'],
            ['role' => 'assistant', 'content' => 'Druga odpowiedź'],
        ]);

    PageAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Drugie pytanie')
        && $prompt->model === 'test-model'
        && collect($prompt->messages)->pluck('content')->all() === ['Pierwsze pytanie', 'Pierwsza odpowiedź']);
});

it('rejects an empty message without calling the provider', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake();

    Livewire::test(AiAssistant::class)
        ->set('prompt', '')
        ->call('send')
        ->assertHasErrors(['prompt' => 'required'])
        ->assertSet('messages', []);

    PageAssistant::assertNeverPrompted();
});

it('shows a retry message when the provider is overloaded', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake(fn () => throw new ProviderOverloadedException('Overloaded'));

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Pytanie')
        ->call('send')
        ->call('reply')
        ->assertSet('error', __('admin.ai_assistant.errors.unavailable'));
});

it('shows a settings message when the provider call fails', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake(fn () => throw new RuntimeException('Provider error'));

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Pytanie')
        ->call('send')
        ->call('reply')
        ->assertSet('error', __('admin.ai_assistant.errors.failed'));
});

it('refuses to send messages when ai is disabled', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Pytanie')
        ->call('send')
        ->assertForbidden();
});