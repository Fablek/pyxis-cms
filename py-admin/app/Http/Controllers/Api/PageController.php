<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        // 1. Break the slug into pieces to extract the last segment
        $segments = explode('/', $slug);
        $lastSegment = end($segments);

        // 2. Looking for the page after the last segment
        // Immediately filter out what is supposed to be hidden from the world
        $page = Page::with('parent')
            ->where('slug', $lastSegment)
            ->first();

        // 3. Validation: Page exist?
        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // 4. Hierarchy Validation:
        // Check if the calculated full_url matches what the user entered. 
        // This prevents someone from entering /zespol instead of /about-us/zespol.
        $calculatedPath = trim($page->full_url, '/');
        $requestedPath = trim($slug, '/');

        if ($calculatedPath !== $requestedPath) {
            return response()->json([
                'message' => 'Page not found at this path',
                'debug' => [
                    'expected' => $calculatedPath,
                    'received' => $requestedPath
                ]
            ], 404);
        }

        // 5. Visibility Validation:
        // For now, we're blocking 'private' until we can log in, but if child is published it should be visible
        if (!$this->allParentsPublished($page)) {
            return response()->json(['message' => 'One of the parent pages is not published'], 404);
        }

        // 6. We return clean data for the Frontend
        return response()->json([
            'id' => $page->id,
            'title' => $page->title,
            'content' => $page->content,
            'seo' => $page->seo,
            'full_url' => $page->full_url,
            'published_at' => $page->published_at,
            'is_password_protected' => $page->visibility === 'password',
        ]);
    }

    /**
     * Recursively checks if each parent up the tree is "live"
     */
    private function allParentsPublished($page) : bool 
    {
        $current = $page->parent;
        while ($current) {
            if (!$current->isLive()) {
                return false;
            }
            $current = $current->parent;
        }
        return true;
    }
}
