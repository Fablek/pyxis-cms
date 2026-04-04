<?php

namespace App\Services;

class PreviewSignatureService 
{
    public function verifySignature(string $path, int $expires, string $signature): bool 
    {
        if ($expires < now()->timestamp) {
            return false;
        }

        $validSignature = hash_hmac('sha256', "{$path}|{$expires}", config('app.key'));

        return hash_equals($validSignature, $signature);
    }

    public function generatePreviewToken(): string {
        $config = config('pyxis.preview');
        return encrypt($config['secret']);
    }
}