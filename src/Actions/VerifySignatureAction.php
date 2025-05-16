<?php

namespace FriendsOfBotble\SePay\Actions;

use Illuminate\Support\Facades\File;

final class VerifySignatureAction
{
    public function __invoke(array $data): bool
    {
        $publicKeyPath = plugin_path('fob-sepay/resources/keys/public.pem');

        if (! File::exists($publicKeyPath)) {
            return false;
        }

        $publicKey = File::get($publicKeyPath);
        $dataToVerify = "{$data['access_token']}.{$data['state']}";
        $signature = base64_decode($data['signature']);

        return openssl_verify(
            $dataToVerify,
            $signature,
            openssl_pkey_get_public($publicKey),
            OPENSSL_ALGO_SHA256
        ) === 1;
    }
}
