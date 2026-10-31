<?php

namespace App\Livewire;

use App\Services\AiSettings;
use App\Services\PageAssistantChat;
use Illuminate\Contracts\View\View;
use Laravel\Ai\Exceptions\FailoverableException;
use Livewire\Attributes\Locked;
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

    #[Locked]
    public ?string $error = null;

    #[Validate('required|string|max:2000')]
    public string $prompt = '';

    /**
     * Shows the editor's message at once; the slow provider call runs in reply().
     */
    public function send(): void
    {
        $this->ensureAvailable();
        $this->validate();

        $this->messages[] = ['role' => 'user', 'content' => $this->prompt];
        $this->prompt = '';
        $this->error = null;

        $this->js('$wire.reply()');
    }

    public function reply(): void
    {
        $this->ensureAvailable();

        $history = $this->messages;
        $lastMessage = array_pop($history);

        if (($lastMessage['role'] ?? null) !== 'user') {
            return;
        }

        try {
            $this->messages[] = [
                'role' => 'assistant',
                'content' => app(PageAssistantChat::class)->reply($history, $lastMessage['content']),
            ];
        } catch (FailoverableException $exception) {
            report($exception);
            $this->error = __('admin.ai_assistant.errors.unavailable');
        } catch (Throwable $exception) {
            report($exception);
            $this->error = __('admin.ai_assistant.errors.failed');
        }
    }

    public function render(): View
    {
        return view('filament.ai-assistant');
    }

    private function ensureAvailable(): void
    {
        abort_unless(auth()->check() && app(AiSettings::class)->isEnabled(), 403);
    }
}
