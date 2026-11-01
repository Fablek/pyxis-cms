<?php

namespace App\Livewire;

use App\Enums\AssistantQuickAction;
use App\Services\AiSettings;
use App\Services\PageAssistantChat;
use Illuminate\Contracts\View\View;
use Laravel\Ai\Exceptions\FailoverableException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

class AiAssistant extends Component
{
    /**
     * @var array<int, array{role: string, content: string}>
     */
    #[Locked]
    public array $messages = [];

    /**
     * @var array<int, array{path: string, label: string, before: string, after: string}>
     */
    #[Locked]
    public array $proposals = [];

    /**
     * @var array<int, array{type: string, label: string, data: array<string, string>}>
     */
    #[Locked]
    public array $blockProposals = [];

    #[Locked]
    public bool $isWaitingForReply = false;

    #[Locked]
    public ?string $error = null;

    #[Validate('required|string|max:2000')]
    public string $prompt = '';

    /**
     * Shows the editor's message at once and asks the page form for its current fields.
     */
    public function send(): void
    {
        $this->ensureAvailable();
        $this->validate();

        $this->startReply($this->prompt);
        $this->prompt = '';
    }

    public function sendQuickAction(AssistantQuickAction $action): void
    {
        $this->ensureAvailable();

        $this->startReply($action->prompt());
    }

    /**
     * @param  array<int, array{path: string, label: string, type: string, value: string}>  $fields
     * @param  array<int, array{type: string, label: string, fields: array<int, array{name: string, label: string, type: string}>}>  $blocks
     */
    #[On('ai-assistant-fields-provided')]
    public function reply(array $fields, array $blocks = []): void
    {
        $this->ensureAvailable();

        $this->isWaitingForReply = false;

        $history = $this->messages;
        $lastMessage = array_pop($history);

        if (($lastMessage['role'] ?? null) !== 'user') {
            return;
        }

        try {
            $reply = app(PageAssistantChat::class)->reply($history, $lastMessage['content'], $fields, $blocks);

            $this->messages[] = ['role' => 'assistant', 'content' => $reply['message']];
            $this->proposals = $reply['proposals'];
            $this->blockProposals = $reply['block_proposals'];
        } catch (FailoverableException $exception) {
            report($exception);
            $this->error = __('admin.ai_assistant.errors.unavailable');
        } catch (Throwable $exception) {
            report($exception);
            $this->error = __('admin.ai_assistant.errors.failed');
        }
    }

    public function applyProposal(int $index): void
    {
        if (isset($this->proposals[$index])) {
            $this->dispatchAcceptedChange($this->proposals[$index]);
        }

        $this->rejectProposal($index);
    }

    public function applyAllProposals(): void
    {
        foreach ($this->proposals as $proposal) {
            $this->dispatchAcceptedChange($proposal);
        }

        foreach ($this->blockProposals as $blockProposal) {
            $this->dispatchAcceptedBlock($blockProposal);
        }

        $this->proposals = [];
        $this->blockProposals = [];
    }

    public function applyBlockProposal(int $index): void
    {
        if (isset($this->blockProposals[$index])) {
            $this->dispatchAcceptedBlock($this->blockProposals[$index]);
        }

        $this->rejectBlockProposal($index);
    }

    public function rejectBlockProposal(int $index): void
    {
        unset($this->blockProposals[$index]);

        $this->blockProposals = array_values($this->blockProposals);
    }

    public function rejectProposal(int $index): void
    {
        unset($this->proposals[$index]);

        $this->proposals = array_values($this->proposals);
    }

    public function render(): View
    {
        return view('filament.ai-assistant');
    }

    private function startReply(string $prompt): void
    {
        $this->messages[] = ['role' => 'user', 'content' => $prompt];
        $this->proposals = [];
        $this->blockProposals = [];
        $this->error = null;
        $this->isWaitingForReply = true;

        $this->dispatch('ai-assistant-fields-requested');
    }

    /**
     * @param  array{path: string, label: string, before: string, after: string}  $proposal
     */
    private function dispatchAcceptedChange(array $proposal): void
    {
        $this->dispatch('ai-assistant-change-accepted', path: $proposal['path'], value: $proposal['after']);
    }

    /**
     * @param  array{type: string, label: string, data: array<string, string>}  $blockProposal
     */
    private function dispatchAcceptedBlock(array $blockProposal): void
    {
        $this->dispatch('ai-assistant-block-accepted', type: $blockProposal['type'], data: $blockProposal['data']);
    }

    private function ensureAvailable(): void
    {
        abort_unless(auth()->check() && app(AiSettings::class)->isEnabled(), 403);
    }
}
