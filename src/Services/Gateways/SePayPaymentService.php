<?php

namespace FriendsOfBotble\SePay\Services\Gateways;

use Botble\Payment\Enums\PaymentStatusEnum;

class SePayPaymentService
{
    public function execute(array $data): string
    {
        $chargeId = get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SDH');

        $chargeId .= sprintf('%\'.09d', (int) (microtime(true) * 10));

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'charge_id' => $chargeId,
            'order_id' => $data['order_id'],
            'customer_id' => $data['customer_id'],
            'customer_type' => $data['customer_type'],
            'payment_channel' => SEPAY_PAYMENT_METHOD_NAME,
            'status' => PaymentStatusEnum::PENDING,
        ]);

        return $chargeId;
    }
}
