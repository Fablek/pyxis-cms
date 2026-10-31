<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

class AiSettings
{
    /**
     * Text providers selectable in the panel: SDK provider name => label.
     *
     * @var array<string, string>
     */
    public const PROVIDERS = [
        'anthropic' => 'Anthropic',
        'openai' => 'OpenAI',
        'gemini' => 'Google Gemini',
        'mistral' => 'Mistral',
    ];

    public function isEnabled(): bool
    {
        return (bool) Setting::get('ai_enabled', false)
            && filled($this->provider())
            && filled($this->apiKey());
    }

    public function provider(): ?string
    {
        return Setting::get('ai_provider');
    }

    public function model(): ?string
    {
        return Setting::get('ai_model');
    }

    public function hasApiKey(): bool
    {
        return filled(Setting::get('ai_api_key'));
    }

    /**
     * Returns null also when the key cannot be decrypted, e.g. after APP_KEY has changed.
     */
    public function apiKey(): ?string
    {
        $encryptedKey = Setting::get('ai_api_key');

        if (blank($encryptedKey)) {
            return null;
        }

        return rescue(fn (): string => Crypt::decryptString($encryptedKey), report: false);
    }

    public function storeApiKey(string $apiKey): void
    {
        Setting::set('ai_api_key', Crypt::encryptString($apiKey));
    }
}
