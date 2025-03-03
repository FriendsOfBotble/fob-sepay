<?php

namespace FriendsOfBotble\SePay\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class OAuthController extends BaseController
{
    public function connect()
    {
        $state = bin2hex(random_bytes(16));

        session()->put('sepay_oauth_state', $state);

        $queryParams = http_build_query([
            'callback_url' => route('sepay.oauth.callback'),
            'state' => $state,
        ]);

        return redirect()->away("http://friendsofbotble.test/oauth/sepay/init?$queryParams");
    }

    public function callback(Request $request)
    {
        $validated = $request->validate([
            'access_token' => 'required|string',
            'refresh_token' => 'required|string',
            'expires_in' => 'required|integer',
            'state' => 'required|string',
            'signature' => 'required|string',
        ]);

        if (! $this->verifySignature($validated)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        setting()->set([
            'sepay_access_token' => $validated['access_token'],
            'sepay_refresh_token' => $validated['refresh_token'],
            'sepay_expired_at' => now()->addSeconds($validated['expires_in']),
            'sepay_connected_at' => now(),
        ])->save();

        return response()->json(['success' => true]);
    }

    public function getCallback()
    {
        return <<<HTML
            <script>
                window.opener.location.reload();
                window.close();
            </script>
        HTML;
    }

    public function disconnect()
    {
        setting()->set([
            'sepay_access_token' => null,
            'sepay_refresh_token' => null,
            'sepay_expired_at' => null,
            'sepay_connected_at' => null,
        ])->save();

        Cache::forget('sepay.profile');
        Cache::forget('sepay.bank-accounts');

        return $this
            ->httpResponse()
            ->setMessage('Disconnected successfully')
            ->setData(['success' => true]);
    }

    protected function verifySignature(array $data): bool
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
