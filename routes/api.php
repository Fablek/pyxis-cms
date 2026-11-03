<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\PreviewController;
use App\Http\Middleware\CheckPreviewToken;
use Illuminate\Support\Facades\Route;

// Catch-all route for parties.
// '{slug}' with the '.*' condition will allow forward slashes to be passed in the address.
Route::get('/pages/{slug?}', [PageController::class, 'show'])
    ->middleware(CheckPreviewToken::class)
    ->where('slug', '.*');

Route::get('/preview/verify', [PreviewController::class, 'verify']);

Route::get('/site', [SiteController::class, 'show']);