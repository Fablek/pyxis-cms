<?php

namespace App\Services;

use App\Models\Page;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asks the frontend to purge its cache for a page after it has been published.
 *
 * Failures are logged and never interrupt publishing.
 */
class FrontendRevalidator
{
    /**
     * Notifies every configured frontend. Returns true only when all of them accepted the request.
     */
    public function revalidate(Page $page): bool
    {
        $urls = config('pyxis.revalidate.urls', []);

        if ($urls === []) {
            Log::warning('Frontend revalidation skipped: no frontend URLs configured (REVALIDATE_URLS).');

            return false;
        }

        $results = collect($urls)->map(
            fn (string $frontendUrl): bool => $this->notify(rtrim($frontendUrl, '/').'/api/revalidate', $page)
        );

        return ! $results->contains(false);
    }

    protected function notify(string $url, Page $page): bool
    {
        try {
            $response = Http::timeout(5)->post($url, [
                'secret' => config('pyxis.revalidate.token'),
                'path' => $page->full_url,
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Frontend revalidation failed: '.$exception->getMessage(), ['url' => $url]);

            return false;
        }

        if ($response->failed()) {
            Log::warning('Frontend rejected the revalidation request.', [
                'url' => $url,
                'path' => $page->full_url,
                'status' => $response->status(),
            ]);

            return false;
        }

        return true;
    }
}
