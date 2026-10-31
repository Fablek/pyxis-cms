<?php

namespace App\Livewire;

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

        $this->messages[] = ['role' => 'user', 'content' => $this->prompt];
        $this->prompt = '';
        $this->proposals = [];
        $this->error = null;
        $this->isWaitingForReply = true;

        $this->dispatch('ai-assistant-fields-requested');
    }

    /**
     * @param  array<int, array{path: string, label: string, type: string, value: string}>  $fields
     */
    #[On('ai-assistant-fields-provided')]
    public function reply(array $fields): void
    {
        $this->ensureAvailable();

        $this->isWaitingForReply = false;

        $history = $this->messages;
        $lastMessage = array_pop($history);

        if (($lastMessage['role'] ?? null) !== 'user') {
            return;
        }

        try {
            $reply = app(PageAssistantChat::class)->reply($history, $lastMessage['content'], $fields);

            $this->messages[] = ['role' => 'assistant', 'content' => $reply['message']];
            $this->proposals = $reply['proposals'];
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

        $this->proposals = [];
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

    /**
     * @param  array{path: string, label: string, before: string, after: string}  $proposal
     */
    private function dispatchAcceptedChange(array $proposal): void
    {
        $this->dispatch('ai-assistant-change-accepted', path: $proposal['path'], value: $proposal['after']);
    }

    private function ensureAvailable(): void
    {
        abort_unless(auth()->check() && app(AiSettings::class)->isEnabled(), 403);
    }
}