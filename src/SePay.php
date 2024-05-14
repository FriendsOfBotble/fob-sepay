<?php

namespace FriendsOfBotble\SePay;

use Botble\Payment\Enums\PaymentMethodEnum;
use Illuminate\Support\Arr;

class SePay
{
    public static function getQRCodeUrl(float $amount, string $chargeId): string
    {
        return 'https://qr.sepay.vn/img?' . http_build_query([
            'acc' => get_payment_setting('account_number', SEPAY_PAYMENT_METHOD_NAME),
            'bank' => get_payment_setting('bank', SEPAY_PAYMENT_METHOD_NAME),
            'amount' => $amount,
            'des' => $chargeId,
            'template' => 'compact',
        ]);
    }

    public static function getBanksList(): array
    {
        return [
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
    }

    public static function getBankById(string $id): string
    {
        return Arr::get(static::getBanksList(), $id, 'vietcombank');
    }

    public static function getChargeIdFrom(string $content): ?string
    {
        $prefix = get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SHD');

        preg_match('/(' . preg_quote($prefix, '/') . '\d+)/', $content, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        return null;
    }
}
