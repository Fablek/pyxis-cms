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
];