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
        return [
            'bank' => get_payment_setting('bank', SEPAY_PAYMENT_METHOD_NAME) ?? 'Vietcombank',
            'bankLogo' => get_payment_setting('bank_logo', SEPAY_PAYMENT_METHOD_NAME),
            'bankShortName' => get_payment_setting('bank_short_name', SEPAY_PAYMENT_METHOD_NAME),
            'bankBrandName' => get_payment_setting('bank_brand_name', SEPAY_PAYMENT_METHOD_NAME),
            'bankAccountNumber' => get_payment_setting('bank_account_number', SEPAY_PAYMENT_METHOD_NAME),
            'bankAccountHolder' => get_payment_setting('bank_account_holder', SEPAY_PAYMENT_METHOD_NAME),
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
