<?php

namespace FriendsOfBotble\SePay\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use FriendsOfBotble\SePay\Actions\VerifySignatureAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        return redirect()->away(SEPAY_FOB_URL . "/oauth/sepay/init?$queryParams");
    }

    public function callback(Request $request, VerifySignatureAction $verifySignatureAction)
    {
        $validated = $request->validate([
            'access_token' => 'required|string',
            'refresh_token' => 'required|string',
            'expires_in' => 'required|integer',
            'state' => 'required|string',
            'signature' => 'required|string',
        ]);

        if (! $verifySignatureAction($validated)) {
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
            'sepay_webhook_id' => null,
        ])->save();

        Cache::forget('sepay.profile');
        Cache::forget('sepay.bank-accounts');

        return $this
            ->httpResponse()
            ->setMessage('Ngắt kết nối với SePay thành công')
            ->setData(['success' => true]);
    }
}
