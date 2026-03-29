<?php

namespace App\Http\Resources;

use App\Enums\PageVisibility;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if preview token is in the headers
        $isPreview = $request->attributes->get('is_preview', false);

        // Visibility logic:
        // Only hide content if the page has a password AND we are not in preview mode.
        $isProtected = $this->visibility === PageVisibility::PASSWORD;
        $shouldHideContent = $isProtected && !$isPreview;

        $isHomepage = (string)$this->id === (string)Setting::get('homepage_id');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $shouldHideContent ? null : $this->getResolvedContent($isPreview),
            'seo' => $this->seo,
            'full_url' => $isHomepage ? '/' : $this->full_url,
            'published_at' => $this->published_at,
            'is_password_protected' => $isProtected,
            'is_preview' => $isPreview,
        ];
    }

    /**
     * The main method serving content to the API.
     * Decides whether to send the LIVE or DRAFT version.
     */
    private function getResolvedContent(bool $isPreview = false): ?array 
    {
        if ($isPreview) {
            // In preview mode, the scratchpad takes priority.
            // If the draft is empty, fallback to the original content.
            return $this->content_draft ?? $this->content;
        }

        // For regular users, always only official content.
        return $this->content;
    }
}
