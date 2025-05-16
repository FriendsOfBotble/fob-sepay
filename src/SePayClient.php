<?php

namespace FriendsOfBotble\SePay;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        return $this->request('get', 'bank-accounts');
    }

    public function bankAccount($id): ?object
    {
        return Cache::remember("sepay.bank-account.$id", 60 * 60, function () use ($id) {
            return (object) $this->request('get', "bank-accounts/$id");
        });
    }

    public function bankSubAccounts(int $bankAccountId)
    {
        return $this->request('get', "bank-accounts/$bankAccountId/sub-accounts");
    }

    public function webhook(int $id): ?array
    {
        return $this->request('get', "webhooks/$id");
    }

    public function createWebhook(array $data): array
    {
        $apiKey = bin2hex(random_bytes(16));

        setting()->set('sepay_api_key', $apiKey)->save();

        return $this->request('post', 'webhooks', [
            'name' => sprintf('FOB SePay - %s', config('app.name')),
            'event_type' => 'In_only',
            'authen_type' => 'Api_Key',
            'api_key' => $apiKey,
            'webhook_url' => route('sepay.webhook'),
            'is_verify_payment' => 1,
            'skip_if_no_code' => 1,
            'request_content_type' => 'Json',
            'only_va' => 0,
            ...$data,
        ]);
    }

    public function updateWebhook(int $id, array $data): array
    {
        return $this->request('patch', "webhooks/$id", $data);
    }

    public function request(string $method, string $url, array $data = []): array
    {
        try {
            $response = Http::baseUrl('https://my.sepay.vn/api/v1')
                ->withToken(setting()->get('sepay_access_token'))
                ->$method($url, $data);

            if ($response->unauthorized()) {
                try {
                    $this->refreshToken();

                    $response = Http::baseUrl('https://my.sepay.vn/api/v1')
                        ->withToken(setting()->get('sepay_access_token'))
                        ->$method($url, $data);
                } catch (Exception $e) {
                    setting()->set([
                        'sepay_access_token' => null,
                        'sepay_refresh_token' => null,
                        'sepay_expired_at' => null,
                        'sepay_connected_at' => null,
                    ])->save();

                    Cache::forget('sepay.profile');
                    Cache::forget('sepay.bank-accounts');

                    throw new Exception('Token đã hết hạn. Vui lòng kết nối lại tài khoản SePay.');
                }
            }

            $data = $response->json();

            if (isset($data['status']) && $data['status'] !== 'success') {
                throw new Exception($data['message'] ?? $data['messages']['error'], $response->status());
            }

            return $data['data'] ?? [];
        } catch (Exception $e) {
            Log::error('SePay API error: ' . $e->getMessage());

            throw $e;
        }
    }

    protected function refreshToken(): void
    {
        $refreshToken = setting()->get('sepay_refresh_token');

        if (! $refreshToken) {
            throw new Exception('Refresh token not found. Please reconnect your SePay account.');
        }

        $response = Http::post(SEPAY_FOB_URL . '/oauth/sepay/token', [
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
