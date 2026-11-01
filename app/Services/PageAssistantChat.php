<?php

namespace App\Services;

use App\Ai\Agents\PageAssistant;
use App\Models\Setting;

class PageAssistantChat
{
    /**
     * Keeps the prompt size, and so its cost, reasonable in long conversations.
     */
    private const MAX_HISTORY_MESSAGES = 20;

    public function __construct(private AiSettings $aiSettings) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @param  array<int, array{path: string, label: string, type: string, value: string}>  $fields
     * @return array{message: string, proposals: array<int, array{path: string, label: string, before: string, after: string}>}
     */
    public function reply(array $history, string $prompt, array $fields): array
    {
        $this->aiSettings->configureProvider();

        $assistant = PageAssistant::make(language: Setting::get('site_language', 'pl'))
            ->withMessages(array_slice($history, -self::MAX_HISTORY_MESSAGES));

        $response = $assistant->prompt(
            'Page fields: '.json_encode($fields, JSON_UNESCAPED_UNICODE)."\n\nEditor's request: {$prompt}",
            provider: $this->aiSettings->provider(),
            model: $this->aiSettings->model() ?: null,
        );

        return [
            'message' => $response['message'],
            'proposals' => $this->proposals($response['changes'], $fields),
        ];
    }

    /**
     * Keeps only real changes of the fields the assistant was given.
     *
     * @param  array<int, array{path: string, value: string}>  $changes
     * @param  array<int, array{path: string, label: string, type: string, value: string}>  $fields
     * @return array<int, array{path: string, label: string, before: string, after: string}>
     */
    private function proposals(array $changes, array $fields): array
    {
        $fieldsByPath = array_column($fields, null, 'path');

        return collect($changes)
            ->filter(fn (array $change): bool => isset($fieldsByPath[$change['path']])
                && $fieldsByPath[$change['path']]['value'] !== $change['value'])
            ->unique('path')
            ->map(fn (array $change): array => [
                'path' => $change['path'],
                'label' => $fieldsByPath[$change['path']]['label'],
                'before' => $fieldsByPath[$change['path']]['value'],
                'after' => $change['value'],
            ])
            ->values()
            ->all();
    }
}
