<?php

namespace App\Filament\Resources\PageResource\Concerns;

use App\Livewire\AiAssistant;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
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

        $this->dispatch('ai-assistant-fields-provided', fields: $fields, blocks: $this->aiAssistantBlocks())->to(AiAssistant::class);
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
     * Adds an accepted new block to the page content. It is stored only when the editor saves the page.
     *
     * @param  array<string, string>  $data
     */
    #[On('ai-assistant-block-accepted')]
    public function addAiAssistantBlock(string $type, array $data): void
    {
        $builder = $this->aiAssistantContentBuilder();
        $block = collect($builder?->getBlockPickerBlocks())->first(fn (Block $block): bool => $block->getName() === $type);

        if ($block === null) {
            return;
        }

        $data = array_intersect_key($data, array_flip(array_map(
            fn (Field $field): string => $field->getName(),
            $this->aiAssistantBlockFields($block),
        )));

        $items = $builder->getRawState() ?? [];
        $items[$builder->generateUuid() ?? count($items)] = ['type' => $type, 'data' => $data];

        $builder->rawState($items);
        $builder->getChildSchema(array_key_last($items))->fill($data);
    }

    /**
     * The blocks that can be added to the page, with the text fields the assistant may fill in.
     *
     * @return array<int, array{type: string, label: string, fields: array<int, array{name: string, label: string, type: string}>}>
     */
    private function aiAssistantBlocks(): array
    {
        return collect($this->aiAssistantContentBuilder()?->getBlockPickerBlocks())
            ->map(fn (Block $block): array => [
                'type' => $block->getName(),
                'label' => (string) $block->getLabel(),
                'fields' => array_map(fn (Field $field): array => [
                    'name' => $field->getName(),
                    'label' => (string) $field->getLabel(),
                    'type' => $field instanceof RichEditor ? 'html' : 'text',
                ], $this->aiAssistantBlockFields($block)),
            ])
            ->filter(fn (array $block): bool => filled($block['fields']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, Field>
     */
    private function aiAssistantBlockFields(Block $block): array
    {
        return array_values(array_filter(
            $block->getDefaultChildComponents(),
            fn (mixed $component): bool => $this->isAiAssistantTextField($component),
        ));
    }

    private function aiAssistantContentBuilder(): ?Builder
    {
        return collect($this->form->getFlatFields())
            ->first(fn (Field $field): bool => $field instanceof Builder);
    }

    private function isAiAssistantTextField(mixed $component): bool
    {
        return $component instanceof TextInput || $component instanceof Textarea || $component instanceof RichEditor;
    }

    /**
     * The text fields of the page content and SEO, keyed by state path. Fields such as the slug or the password stay out of reach.
     *
     * @return array<string, Field>
     */
    private function aiAssistantFields(): array
    {
        return collect($this->form->getFlatFields())
            ->filter(fn (Field $field): bool => $this->isAiAssistantTextField($field)
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
