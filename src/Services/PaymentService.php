<?php

namespace FriendsOfBotble\SePay\Services;

class PaymentService
{
    public function generateChargeId(): string
    {
        $prefix = get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SDH');

        return $prefix . sprintf('%\'.09d', (int) (microtime(true) * 10));
    }
}
