<?php

namespace FriendsOfBotble\SePay\Services;

use Botble\Payment\Models\Payment;

class PaymentService
{
    public function generateChargeId(): string
    {
        $prefix = get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SDH');
        $number = (int) (microtime(true) * 10);

        // Orders placed within the same 100ms would otherwise share a code and a webhook could complete the wrong one.
        do {
            $chargeId = $prefix . sprintf('%\'.09d', $number++);
        } while (Payment::query()->where('charge_id', $chargeId)->exists());

        return $chargeId;
    }
}
