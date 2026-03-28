<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Setting;

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

    public function getPageForViewing(?string $slug, bool $isPreview): ?Page
    {
        $homepageId = Setting::get('homepage_id');

        $page = $this->resolvePage($slug, $homepageId);

        if (!$page) {
            throw new \App\Exceptions\PageNotFoundException('Page not found');
        }

        // Basic Validations (Quick Exits)
        if (!$page->isLive() && !$isPreview) {
            throw new \App\Exceptions\PageNotFoundException('Page not found');
        }

        // Blocking access to the home page by its slug
        if ($slug && (string)$page->id === (string)$homepageId) {
            throw new \App\Exceptions\PageNotFoundException('Use root path for homepage');
        }

        // Path validation (for subpages only)
        if ($slug && trim($page->full_url, '/') !== trim($slug, '/')) {
            throw new \App\Exceptions\PageNotFoundException('Path mismatch');
        }

        // Parent validation
        if (!$isPreview && !$this->allParentsPublished($page)) {
            throw new \App\Exceptions\PageNotFoundException('One of the parent pages is not published');
        }

        return $page;
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