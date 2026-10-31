<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Override;
use Stringable;

class SeoWriter implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public string $language) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<INSTRUCTIONS
        You write SEO metadata for web pages.
        Base it only on the page text you are given and do not invent facts.
        The title has at most 60 characters and the description at most 160.
        Write in the language with the code: {$this->language}.
        INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required(),
            'description' => $schema->string()->required(),
        ];
    }
}
