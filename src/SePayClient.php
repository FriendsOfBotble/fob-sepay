<?php

namespace FriendsOfBotble\SePay;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SePayClient
{
    public function isConnected(): bool
    {
        return setting()->get('sepay_connected_at') !== null;
    }

    public function profile(): ?object
    {
        return Cache::remember('sepay.profile', 60 * 60, function () {
            return (object) $this->request('get', 'me');
        });
    }

    public function company(): ?array
    {
        return $this->request('get', 'company');
    }

    public function bankAccounts(): array
    {
        return Cache::remember('sepay.bank-accounts', 60 * 60, function () {
            return $this->request('get', 'bank-accounts');
        });
    }

    public function bankAccount($id): ?object
    {
        return Cache::remember("sepay.bank-account.$id", 60 * 60, function () use ($id) {
            return (object) $this->request('get', "bank-accounts/$id");
        });
    }

    public function bankSubAccounts(int $bankAccountId)
    {
        return Cache::remember("sepay.bank-sub-accounts.$bankAccountId", 60 * 60, function () use ($bankAccountId) {
            return $this->request('get', "bank-accounts/$bankAccountId/sub-accounts");
        });
    }

    public function webhook(int $id): ?array
    {
        return $this->request('get', "webhooks/$id");
    }

    public function createWebhook(array $data): array
    {
        return $this->request('post', 'webhooks', [
            'name' => sprintf('FOB SePay %s', config('app.url')),
            'event_type' => 'In_only',
            'authen_type' => 'Api_Key',
            'api_key' => base64_encode(random_bytes(32)),
            // 'webhook_url' => route('sepay.webhook'),
            'webhook_url' => 'https://shofy.botble.com/sepay/webhook',
            'is_verify_payment' => true,
            'request_content_type' => 'Json',
            ...$data,
        ]);
    }

    public function updateWebhook(int $id, array $data): array
    {
        return $this->request('patch', "webhooks/$id", $data);
    }

    public function request(string $method, string $url, array $data = []): array
    {
        $response = Http::baseUrl('https://my.sepay.vn/api/v1')
            ->withToken(setting()->get('sepay_access_token'))
            ->$method($url, $data);

        if ($response->unauthorized()) {
            $this->refreshToken();

            $response = Http::baseUrl('https://my.sepay.vn/api/v1')
                ->withToken(setting()->get('sepay_access_token'))
                ->$method($url, $data);
        }

        $data = $response->json();

        if (isset($data['status']) && $data['status'] !== 'success') {
            throw new Exception($data['message'] ?? $data['messages']['error'], $response->status());
        }

        return $data['data'] ?? [];
    }

    protected function refreshToken(): void
    {
        $refreshToken = setting()->get('sepay_refresh_token');

        if (! $refreshToken) {
            throw new Exception('Refresh token not found. Please reconnect your SePay account.');
        }

        $response = Http::post('http://friendsofbotble.test/oauth/sepay/token', [
            'refresh_token' => $refreshToken,
        ]);

        $data = $response->json();

        if (! isset($data['access_token']) || ! isset($data['refresh_token'])) {
            throw new Exception('Failed to refresh SePay token. Please reconnect your account.');
        }

        setting()->set([
            'sepay_access_token' => $data['access_token'],
            'sepay_refresh_token' => $data['refresh_token'],
            'sepay_expired_at' => now()->addSeconds($data['expires_in']),
        ])->save();
    }
}
