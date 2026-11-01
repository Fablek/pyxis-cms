<?php

use App\Ai\Agents\PageAssistant;
use App\Enums\AssistantQuickAction;
use App\Filament\Resources\PageResource;
use App\Livewire\AiAssistant;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Services\AiSettings;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Prompts\AgentPrompt;
use Livewire\Livewire;
use Illuminate\Support\Facades\Lang;

function enableAiAssistant(): void
{
    Setting::set('ai_enabled', true);
    Setting::set('ai_provider', 'anthropic');
    Setting::set('ai_model', 'test-model');
    app(AiSettings::class)->storeApiKey('sk-test-key');
}

/**
 * @return array<int, array{path: string, label: string, type: string, value: string}>
 */
function assistantFields(): array
{
    return [['path' => 'data.title', 'label' => 'Tytuł', 'type' => 'text', 'value' => 'O nas']];
}

function assistantReply(array $changes = [], array $newBlocks = []): array
{
    return ['message' => 'Gotowe', 'changes' => $changes, 'new_blocks' => $newBlocks];
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

it('asks the page form for its fields after a message', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Pytanie')
        ->call('send')
        ->assertSet('prompt', '')
        ->assertSet('isWaitingForReply', true)
        ->assertDispatched('ai-assistant-fields-requested');
});

it('sends the fields and the earlier messages to the provider', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake([assistantReply(), assistantReply()]);

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Pierwsze pytanie')
        ->call('send')
        ->call('reply', assistantFields())
        ->set('prompt', 'Drugie pytanie')
        ->call('send')
        ->call('reply', assistantFields())
        ->assertSet('isWaitingForReply', false)
        ->assertSet('messages', [
            ['role' => 'user', 'content' => 'Pierwsze pytanie'],
            ['role' => 'assistant', 'content' => 'Gotowe'],
            ['role' => 'user', 'content' => 'Drugie pytanie'],
            ['role' => 'assistant', 'content' => 'Gotowe'],
        ]);

    PageAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Drugie pytanie')
        && $prompt->contains('O nas')
        && $prompt->model === 'test-model'
        && collect($prompt->messages)->pluck('content')->all() === ['Pierwsze pytanie', 'Gotowe']);
});

it('turns the changes into proposals with the previous value', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake([assistantReply([['path' => 'data.title', 'value' => 'About us']])]);

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Przetłumacz')
        ->call('send')
        ->call('reply', assistantFields())
        ->assertSet('proposals', [
            ['path' => 'data.title', 'label' => 'Tytuł', 'before' => 'O nas', 'after' => 'About us'],
        ]);
});

it('drops changes of unknown fields and unchanged values', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake([assistantReply([
        ['path' => 'data.status', 'value' => 'published'],
        ['path' => 'data.title', 'value' => 'O nas'],
    ])]);

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Opublikuj')
        ->call('send')
        ->call('reply', assistantFields())
        ->assertSet('proposals', []);
});

it('hands an accepted proposal to the form and removes it', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake([assistantReply([['path' => 'data.title', 'value' => 'About us']])]);

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Przetłumacz')
        ->call('send')
        ->call('reply', assistantFields())
        ->call('applyProposal', 0)
        ->assertDispatched('ai-assistant-change-accepted', path: 'data.title', value: 'About us')
        ->assertSet('proposals', []);
});

it('removes a rejected proposal without changing the form', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake([assistantReply([['path' => 'data.title', 'value' => 'About us']])]);

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Przetłumacz')
        ->call('send')
        ->call('reply', assistantFields())
        ->call('rejectProposal', 0)
        ->assertNotDispatched('ai-assistant-change-accepted')
        ->assertSet('proposals', []);
});

it('turns new blocks into proposals and drops unknown types and fields', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake([assistantReply(newBlocks: [
        ['type' => 'tekst', 'fields' => [['name' => 'body', 'value' => 'Szkic'], ['name' => 'unknown', 'value' => 'X']]],
        ['type' => 'unknown', 'fields' => [['name' => 'body', 'value' => 'Szkic']]],
    ])]);

    $blocks = [['type' => 'tekst', 'label' => 'Tekst', 'fields' => [['name' => 'body', 'label' => 'Treść', 'type' => 'html']]]];

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Napisz szkic')
        ->call('send')
        ->call('reply', assistantFields(), $blocks)
        ->assertSet('blockProposals', [['type' => 'tekst', 'label' => 'Tekst', 'data' => ['body' => 'Szkic']]])
        ->call('applyBlockProposal', 0)
        ->assertDispatched('ai-assistant-block-accepted', type: 'tekst', data: ['body' => 'Szkic'])
        ->assertSet('blockProposals', []);
});

it('rejects an empty message without asking for the fields', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());

    Livewire::test(AiAssistant::class)
        ->set('prompt', '')
        ->call('send')
        ->assertHasErrors(['prompt' => 'required'])
        ->assertNotDispatched('ai-assistant-fields-requested')
        ->assertSet('messages', []);
});

it('shows a retry message when the provider is overloaded', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake(fn () => throw new ProviderOverloadedException('Overloaded'));

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Pytanie')
        ->call('send')
        ->call('reply', assistantFields())
        ->assertSet('isWaitingForReply', false)
        ->assertSet('error', __('admin.ai_assistant.errors.unavailable'));
});

it('shows a settings message when the provider call fails', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());
    PageAssistant::fake(fn () => throw new RuntimeException('Provider error'));

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Pytanie')
        ->call('send')
        ->call('reply', assistantFields())
        ->assertSet('error', __('admin.ai_assistant.errors.failed'));
});

it('has a label and a prompt for every quick action in both languages', function () {
    foreach (['pl', 'en'] as $locale) {
        foreach (AssistantQuickAction::cases() as $action) {
            expect(Lang::has("admin.ai_assistant.quick_actions.{$action->value}.label", $locale))->toBeTrue()
                ->and(Lang::has("admin.ai_assistant.quick_actions.{$action->value}.prompt", $locale))->toBeTrue();
        }
    }
});

it('sends a quick action as a message', function () {
    enableAiAssistant();
    $this->actingAs(User::factory()->create());

    Livewire::test(AiAssistant::class)
        ->call('sendQuickAction', AssistantQuickAction::SEO->value)
        ->assertSet('messages', [['role' => 'user', 'content' => AssistantQuickAction::SEO->prompt()]])
        ->assertDispatched('ai-assistant-fields-requested');
});

it('tells the provider the site language', function () {
    enableAiAssistant();
    Setting::set('site_language', 'en');
    $this->actingAs(User::factory()->create());
    PageAssistant::fake([assistantReply()]);

    Livewire::test(AiAssistant::class)
        ->call('sendQuickAction', AssistantQuickAction::SEO->value)
        ->call('reply', assistantFields());

    PageAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => str_contains((string) $prompt->agent->instructions(), 'language with the code en'));
});

it('refuses to send messages when ai is disabled', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(AiAssistant::class)
        ->set('prompt', 'Pytanie')
        ->call('send')
        ->assertForbidden();
});
