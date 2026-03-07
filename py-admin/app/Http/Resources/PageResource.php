<?php

namespace App\Http\Resources;

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
        $isProtected = $this->visibility === 'password';
        $shouldHideContent = $isProtected;

        $isHomepage = (string)$this->id === (string)Setting::get('homepage_id');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $shouldHideContent ? null : $this->content,
            'seo' => $this->seo,
            'full_url' => $isHomepage ? '/' : $this->full_url,
            'published_at' => $this->published_at,
            'is_password_protected' => $this->visibility === 'password',
        ];
    }
}
