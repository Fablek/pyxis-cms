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

        $lastSegment = collect(explode('/', $slug))->last();

        return Page::with('parent')
            ->where('slug', $lastSegment)
            ->first();
    }

    /**
     * Recursively checks if each parent up the tree is "live"
     */
    public function allParentsPublished($page): bool 
    {
        $current = $page->parent;
        while ($current) {
            if (!$current->isLive()) return false;
            $current = $current->parent;
        }
        return true;
    }
}