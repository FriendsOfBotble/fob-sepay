<?php

namespace FriendsOfBotble\SePay\Services;

use Botble\Payment\Models\Payment;
use Illuminate\Support\Facades\Cache;

class PaymentService
{
    public function generateChargeId(): string
    {
        $prefix = get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SDH');
        $number = (int) (microtime(true) * 10);

        // Orders placed within the same 100ms would otherwise share a code and a webhook could complete
        // the wrong one. Cache::add() is atomic, so concurrent checkouts can't reserve the same code before
        // either payment row is inserted.
        do {
            $chargeId = $prefix . sprintf('%\'.09d', $number++);
        } while (
            ! Cache::add('sepay.charge-id.' . $chargeId, true, now()->addHour())
            || Payment::query()->where('charge_id', $chargeId)->exists()
        );

        return $chargeId;
    }
}
