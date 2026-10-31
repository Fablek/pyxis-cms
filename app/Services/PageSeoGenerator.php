<?php

namespace App\Services;

use App\Ai\Agents\SeoWriter;
use App\Models\Setting;
use Illuminate\Support\Str;

class PageSeoGenerator
{
    /**
     * Keeps the prompt size, and so its cost, reasonable for very long pages.
     */
    private const MAX_PROMPT_LENGTH = 20000;

    public function __construct(private AiSettings $aiSettings) {}

    /**
     * Returns null when the page has no text to base the metadata on.
     *
     * @param  array<mixed>  $content
     * @param  array<mixed>  $fields
     * @return array{title: string, description: string}|null
     */
    public function generate(?string $title, array $content, array $fields): ?array
    {
        $pageText = implode("\n", [...$this->extractText($content), ...$this->extractText($fields)]);

        if (blank($pageText)) {
            return null;
        }

        $this->aiSettings->configureProvider();

        $response = SeoWriter::make(language: Setting::get('site_language', 'pl'))->prompt(
            Str::limit("Page title: {$title}\n\nPage text:\n{$pageText}", self::MAX_PROMPT_LENGTH),
            provider: $this->aiSettings->provider(),
            model: $this->aiSettings->model() ?: null,
        );

        return [
            'title' => Str::limit($response['title'], 255, ''),
            'description' => Str::limit($response['description'], 500, ''),
        ];
    }

    /**
     * Collects editor-written text from builder blocks and field values, skipping block types and media identifiers.
     *
     * @param  array<mixed>  $values
     * @return array<int, string>
     */
    private function extractText(array $values): array
    {
        $texts = [];

        foreach ($values as $key => $value) {
            if (in_array($key, ['type', 'attrs', 'marks'], true)) {
                continue;
            }

            if (is_array($value)) {
                array_push($texts, ...$this->extractText($value));

                continue;
            }

            if (is_string($value) && ! Str::isUuid($value) && filled($text = trim(strip_tags($value)))) {
                $texts[] = $text;
            }
        }

        return $texts;
    }
}
