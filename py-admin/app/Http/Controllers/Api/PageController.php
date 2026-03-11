<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Setting;
use App\Http\Resources\PageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(Request $request, ?string $slug = null)
    {
        $homepageId = Setting::get('homepage_id');

        // Check preview
        $isPreview = false;
        if ($token = $request->header('X-Pyxis-Preview')) {
            try {
                $isPreview = decrypt($token) === 'pyxis-preview-mode';
            } catch (\Exception $e) {}
        }

        // Choosing the right site
        $page = $this->resolvePage($slug, $homepageId);

        // Basic Validations (Quick Exits)
        if (!$page || (!$page->isLive() && !$isPreview)) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // Blocking access to the home page by its slug
        if ($slug && (string)$page->id === (string)$homepageId) {
            return response()->json(['message' => 'Use root path for homepage'], 404);
        }

        // Path validation (for subpages only)
        if ($slug && trim($page->full_url, '/') !== trim($slug, '/')) {
            return response()->json(['message' => 'Path mismatch'], 404);
        }

        // Parent validation
        if (!$isPreview && !$this->allParentsPublished($page)) {
            return response()->json(['message' => 'One of the parent pages is not published'], 404);
        }

        // Returning data from Resource
        return new PageResource($page);
    }

    private function resolvePage(?string $slug, ?string $homepageId): ?Page
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
    private function allParentsPublished($page): bool 
    {
        $current = $page->parent;
        while ($current) {
            if (!$current->isLive()) return false;
            $current = $current->parent;
        }
        return true;
    }
}
