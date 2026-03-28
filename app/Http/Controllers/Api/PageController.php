<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Services\PageService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    protected PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function show(Request $request, ?string $slug = null)
    {
        // Check preview
        $isPreview = $request->attributes->get('is_preview', false);

        try {
            // Choosing the right site
            $page = $this->pageService->getPageForViewing($slug, $isPreview);

            return new PageResource($page);
        } catch (\App\Exceptions\PageNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
