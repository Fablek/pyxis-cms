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
    public function revalidate(Page $page): bool
    {
        $url = rtrim((string) config('app.frontend_url'), '/').'/api/revalidate';

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
