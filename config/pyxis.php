<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pyxis Preview Configuration
    |--------------------------------------------------------------------------
    */

    'preview' => [
        // Name of the header sent by the frontend (SSR)
        'header' => env('PYXIS_PREVIEW_HEADER', 'X-Pyxis-Preview'),

        // Internal identifier encrypted in the token
        'secret' => env('PYXIS_PREVIEW_SECRET', 'pyxis-preview-mode'),

        // Signed URL lifetime (in minutes)
        'ttl' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Cache Revalidation
    |--------------------------------------------------------------------------
    */

    'revalidate' => [
        // Shared secret sent to every frontend after publishing
        'token' => env('REVALIDATE_TOKEN'),

        // Internal addresses of the frontends to notify after publishing (comma separated).
        // Inside Docker use container names, e.g. http://pyxis-view-next:3000
        'urls' => array_values(array_filter(array_map('trim', explode(',', (string) env('REVALIDATE_URLS', env('FRONTEND_URL', '')))))),
    ],
];
