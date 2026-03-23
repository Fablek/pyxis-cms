<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PreviewSignatureService;

class PreviewController extends Controller
{
    protected PreviewSignatureService $signatureService;

    public function __construct(PreviewSignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'path' => 'required|string',
            'expires' => 'required|integer',
            'signature' => 'required|string',
        ]);

        $isValid = $this->signatureService->verifySignature($data['path'], $data['expires'], $data['signature']);

        if (!$isValid) {
            return response()->json(['valid' => false], 403);
        }

        return response()->json([
            'valid' => true,
            'preview_token' => $this->signatureService->generatePreviewToken()
        ]);
    }
}
