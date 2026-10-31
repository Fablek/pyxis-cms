<aside
    x-data="{ open: false }"
    x-effect="document.documentElement.classList.toggle('ai-assistant-open', open)"
    x-on:ai-assistant-toggle.window="open = ! open"
    x-show="open"
    x-cloak
    x-transition:enter="transition duration-200 ease-out"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition duration-150 ease-in"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    aria-label="{{ __('admin.ai_assistant.title') }}"
    class="fixed inset-y-0 right-0 z-30 flex w-full flex-col border-l border-gray-200 bg-white lg:w-96 dark:border-white/10 dark:bg-gray-900"
>
    <header class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-4 dark:border-white/10">
        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('admin.ai_assistant.title') }}</h2>

        <button
            type="button"
            x-on:click="open = false"
            aria-label="{{ __('admin.ai_assistant.close') }}"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:text-gray-900 dark:hover:text-white"
        >
            <x-filament::icon icon="heroicon-o-x-mark" class="size-5" />
        </button>
    </header>

    <div class="flex flex-1 flex-col gap-3 overflow-y-auto p-4 text-sm">
        @if (blank($messages))
            <p class="text-gray-500 dark:text-gray-400">{{ __('admin.ai_assistant.empty') }}</p>
        @endif

         @foreach ($messages as $message)
            <div
                wire:key="ai-message-{{ $loop->index }}"
                @class([
                    'max-w-[85%] whitespace-pre-wrap rounded-lg px-3 py-2',
                    'self-end bg-gray-900 text-white dark:bg-white dark:text-gray-900' => $message['role'] === 'user',
                    'self-start bg-gray-100 text-gray-900 dark:bg-white/5 dark:text-white' => $message['role'] !== 'user',
                ])
            >{{ $message['content'] }}</div>
        @endforeach

        <p wire:loading wire:target="reply" class="text-gray-500 dark:text-gray-400">
            {{ __('admin.ai_assistant.thinking') }}
        </p>

        @if ($error)
            <p role="alert" class="text-danger-600 dark:text-danger-400">{{ $error }}</p>
        @endif
    </div>

    <form wire:submit="send" class="shrink-0 border-t border-gray-200 p-4 dark:border-white/10">
        <textarea
            wire:model="prompt"
            x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.send() }"
            rows="3"
            maxlength="2000"
            placeholder="{{ __('admin.ai_assistant.placeholder') }}"
            aria-label="{{ __('admin.ai_assistant.placeholder') }}"
            class="w-full resize-none rounded-lg border border-gray-200 bg-transparent p-2 text-sm text-gray-900 dark:border-white/10 dark:text-white"
        ></textarea>

        @error('prompt')
            <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
        @enderror

        <div class="mt-2 flex justify-end">
            <x-filament::button type="submit" size="sm" wire:loading.attr="disabled" wire:target="send, reply">
                {{ __('admin.ai_assistant.send') }}
            </x-filament::button>
        </div>
    </form>
</aside>