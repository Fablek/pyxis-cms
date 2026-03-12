<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PreviewController extends Controller
{
    public function verify(Request $request)
    {
        $path = $request->query('path');
        $expires = $request->query('expires');
        $signature = $request->query('signature');

        $validSignature = hash_hmac('sha256', "{$path}|{$expires}", config('app.key'));

        if (!hash_equals($validSignature, $signature)) {
            return response()->json(['valid' => false], 403);
        }

        return response()->json([
            'valid' => true,
            'preview_token' => encrypt('pyxis-preview-mode')
        ]);
    }
}
