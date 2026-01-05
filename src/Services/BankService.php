<?php

namespace FriendsOfBotble\SePay\Services;

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

    public function getBankInfo(): array
    {
        // Use setting() directly for bank identifiers to avoid Language plugin's
        // locale filtering that affects keys containing "name"
        return [
            'bank' => setting('payment_sepay_bank') ?? 'Vietcombank',
            'bankLogo' => setting('payment_sepay_bank_logo'),
            'bankShortName' => setting('payment_sepay_bank_short_name'),
            'bankBrandName' => setting('payment_sepay_bank_brand_name'),
            'bankAccountNumber' => setting('payment_sepay_bank_account_number'),
            'bankAccountHolder' => setting('payment_sepay_bank_account_holder'),
        ];
    }

    public function getQrCodeUrl(string $accountNumber, string $bankShortName, float $amount, string $chargeId): string
    {
        return 'https://qr.sepay.vn/img?' . http_build_query([
            'acc' => $accountNumber,
            'bank' => $bankShortName,
            'amount' => $amount,
            'des' => $chargeId,
            'template' => 'compact',
        ]);
    }
}
