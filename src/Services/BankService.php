<?php

namespace FriendsOfBotble\SePay\Services;

use Exception;
use FriendsOfBotble\SePay\SePayClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BankService
{
    protected array $banks = [
        'vietcombank' => 'Vietcombank',
        'vpbank' => 'VPBank',
        'acb' => 'ACB',
        'sacombank' => 'Sacombank',
        'hdbank' => 'HDBank',
        'vietinbank' => 'VietinBank',
        'techcombank' => 'Techcombank',
        'mbbank' => 'MBBank',
        'bidv' => 'BIDV',
        'msb' => 'MSB',
        'shinhanbank' => 'ShinhanBank',
        'tpbank' => 'TPBank',
        'eximbank' => 'Eximbank',
        'vib' => 'VIB',
        'agribank' => 'Agribank',
        'publicbank' => 'PublicBank',
        'kienlongbank' => 'KienLongBank',
        'ocb' => 'OCB',
    ];

    protected const CACHE_KEY = 'sepay_bank_info';

    protected const CACHE_TTL = 86400; // 24 giờ

    public function getBankInfo(): array
    {
        $cachedInfo = Cache::get(self::CACHE_KEY);
        if ($cachedInfo) {
            return $cachedInfo;
        }

        $bankInfo = $this->fetchBankInfo();
        Cache::put(self::CACHE_KEY, $bankInfo, self::CACHE_TTL);

        return $bankInfo;
    }

    protected function fetchBankInfo(): array
    {
        $client = new SePayClient();
        $bank = $this->banks[get_payment_setting('bank', SEPAY_PAYMENT_METHOD_NAME)] ?? 'Vietcombank';
        $bankAccountNumber = get_payment_setting('account_number', SEPAY_PAYMENT_METHOD_NAME);
        $bankAccountHolder = get_payment_setting('account_holder', SEPAY_PAYMENT_METHOD_NAME);
        $bankShortName = $bank;
        $bankLogo = '';

        try {
            if ($client->isConnected()) {
                $bankAccount = $client->bankAccount(get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME));

                $bank = match (get_payment_setting('bank_display', SEPAY_PAYMENT_METHOD_NAME, 'short_name')) {
                    'full_name' => $bankAccount->bank['full_name'],
                    'short_name' => $bankAccount->bank['short_name'],
                    'full_name_short_name' => "{$bankAccount->bank['full_name']} ({$bankAccount->bank['short_name']})",
                    default => $bank,
                };

                $bankShortName = $bankAccount->bank['short_name'];
                $bankAccountNumber = $bankAccount->account_number;
                $bankAccountHolder = $bankAccount->account_holder_name;
                $bankLogo = $bankAccount->bank['logo_url'];

                if ($bankSubAccountId = get_payment_setting('bank_sub_account_id', SEPAY_PAYMENT_METHOD_NAME)) {
                    $bankSubAccounts = $client->bankSubAccounts($bankAccount->id);

                    $bankSubAccount = collect($bankSubAccounts)
                        ->where('bank_account_id', $bankAccount->id)
                        ->where('id', $bankSubAccountId)
                        ->first();

                    if ($bankSubAccount) {
                        $bankAccountNumber = $bankSubAccount['account_number'];
                        $bankAccountHolder = $bankSubAccount['account_holder_name'] ?: $bankAccountHolder;
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('SePay connection error: ' . $e->getMessage());
        }

        return [
            'bank' => $bank,
            'bankLogo' => $bankLogo,
            'bankAccountNumber' => $bankAccountNumber,
            'bankAccountHolder' => $bankAccountHolder,
            'short_name' => $bankShortName,
            'account_number' => $bankAccountNumber,
        ];
    }

    public function getQrCodeUrl(string $accountNumber, string $bankShortName, float $amount, string $chargeId): string
    {
        try {
            $client = new SePayClient();
            return $client->getQrCodeUrl($accountNumber, $bankShortName, $amount, $chargeId);
        } catch (Exception $e) {
            Log::error('SePay QR code generation error: ' . $e->getMessage());
            return '';
        }
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
