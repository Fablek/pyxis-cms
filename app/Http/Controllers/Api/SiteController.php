<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteController extends Controller
{
    /**
     * Site-wide settings the frontends need on every page, including error pages.
     */
    public function show(): JsonResource
    {
        return JsonResource::make([
            'language' => Setting::get('site_language', 'pl'),
        ]);
    }
}
