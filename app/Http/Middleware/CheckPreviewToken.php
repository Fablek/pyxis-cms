<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPreviewToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isPreview = false;
        $config = config('pyxis.preview');

        if ($token = $request->header($config['header'])) {
            try {
                $isPreview = decrypt($token) === $config['secret'];
            } catch (\Exception $e) {
                Log::warning('Invalid preview token.', [
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);

                return response()->json([
                    'message' => 'Invalid or malformed preview token.'
                ], 403);
            }
        }

        $request->attributes->set('is_preview', $isPreview);

        return $next($request);
    }
}
