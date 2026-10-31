<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class PageAssistant implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
        You are the assistant of the Pyxis CMS page editor.
        You help editors write and improve the content of web pages.
        Reply briefly, in the language the editor writes in.
        INSTRUCTIONS;
    }
}