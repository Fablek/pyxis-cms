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
        $page = Page::where('slug', $lastSegment)
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->first();

        // 3. Validation: Page exist?
        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // 4. Hierarchy Validation:
        // Check if the calculated full_url matches what the user entered. 
        // This prevents someone from entering /zespol instead of /about-us/zespol.
        if (trim($page->full_url, '/') !== trim($slug, '/')) {
             return response()->json(['message' => 'Page not found at this path'], 404);
        }

        // 5. Visibility Validation:
        // For now, we're blocking 'private' until we can log in
        if ($page->visibility === 'private') {
            return response()->json(['message' => 'Unauthorized access'], 403);
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
}
