<?php

namespace App\Enums;

enum AssistantQuickAction: string
{
    case SEO = 'seo';
    case SUMMARY = 'summary';
    case TRANSLATE_EN = 'translate_en';
    case TRANSLATE_PL = 'translate_pl';
    case FORMAL = 'formal';
    case CASUAL = 'casual';
    case SHORTER = 'shorter';
    case CORRECT = 'correct';

    public function label(): string
    {
        return __("admin.ai_assistant.quick_actions.{$this->value}.label");
    }

    public function prompt(): string
    {
        return __("admin.ai_assistant.quick_actions.{$this->value}.prompt");
    }
}
