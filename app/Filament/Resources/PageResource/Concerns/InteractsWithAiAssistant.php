<?php

namespace App\Filament\Resources\PageResource\Concerns;

use App\Livewire\AiAssistant;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

trait InteractsWithAiAssistant
{
    /**
     * Sends the assistant the current, also unsaved, values of the fields it may change.
     */
    #[On('ai-assistant-fields-requested')]
    public function provideAiAssistantFields(): void
    {
        $fields = array_map(fn (Field $field): array => [
            'path' => $field->getStatePath(),
            'label' => (string) $field->getLabel(),
            'type' => $field instanceof RichEditor ? 'html' : 'text',
            'value' => $this->aiAssistantFieldValue($field),
        ], array_values($this->aiAssistantFields()));

        $this->dispatch('ai-assistant-fields-provided', fields: $fields)->to(AiAssistant::class);
    }

    /**
     * Writes an accepted proposal into the form. It is stored only when the editor saves the page.
     */
    #[On('ai-assistant-change-accepted')]
    public function applyAiAssistantChange(string $path, string $value): void
    {
        $field = $this->aiAssistantFields()[$path] ?? null;

        if ($field === null) {
            return;
        }

        $field->state($field instanceof RichEditor
            ? $field->getTipTapEditor()->setContent($value)->getDocument()
            : $value);
    }

    /**
     * The text fields of the page content and SEO, keyed by state path. Fields such as the slug or the password stay out of reach.
     *
     * @return array<string, Field>
     */
    private function aiAssistantFields(): array
    {
        return collect($this->form->getFlatFields())
            ->filter(fn (Field $field): bool => ($field instanceof TextInput || $field instanceof Textarea || $field instanceof RichEditor)
                && Str::is(['data.title', 'data.seo_draft.*', 'data.fields_draft.*', 'data.content_draft.*'], $field->getStatePath()))
            ->keyBy(fn (Field $field): string => $field->getStatePath())
            ->all();
    }

    private function aiAssistantFieldValue(Field $field): string
    {
        $state = $field->getState();

        if ($field instanceof RichEditor && is_array($state)) {
            return $field->getTipTapEditor()->setContent($state)->getHTML();
        }

        return (string) $state;
    }
}
