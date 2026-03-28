<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PageService
{
    public function resolvePage(?string $slug, ?string $homepageId): ?Page
    {
        if (empty($slug) || $slug === '/') {
            return $homepageId ? Page::find($homepageId) : null;
        }

        $segments = explode('/', ltrim($slug, '/'));
        $lastSegment = last($segments);

        $candidates = Page::where('slug', $lastSegment)->get();

        return $candidates->first(function ($page) use ($slug) {
            return trim($page->full_url, '/') === trim($slug, '/');
        });
    }

    /**
     * Recursively checks if each parent up the tree is "live"
     */
    public function allParentsPublished(Page $page): bool 
    {
        return $page->ancestors()
            ->get()
            ->every(fn($parent) => $parent->isLive());
    }
}