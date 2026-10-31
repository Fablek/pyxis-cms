<?php

namespace App\Services;

use App\Ai\Agents\PageAssistant;

class PageAssistantChat
{
    /**
     * Keeps the prompt size, and so its cost, reasonable in long conversations.
     */
    private const MAX_HISTORY_MESSAGES = 20;

    public function __construct(private AiSettings $aiSettings) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function reply(array $history, string $prompt): string
    {
        $this->aiSettings->configureProvider();

        return PageAssistant::make()->withMessages(array_slice($history, -self::MAX_HISTORY_MESSAGES))->prompt(
            $prompt,
            provider: $this->aiSettings->provider(),
            model: $this->aiSettings->model() ?: null,
        )->text;
    }
}
