<?php

namespace FriendsOfBotble\SePay\Services\Gateways;

use Botble\Base\Models\BaseModel;
use Botble\Payment\Enums\PaymentStatusEnum;

class SePayPaymentService
{
    public function execute(array $data): string
    {
        $chargeId = get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SDH');

        if (BaseModel::getTypeOfId() === 'BIGINT') {
            $chargeId .= sprintf('%\'.09d', array_reduce($data['order_id'], fn ($carry, $item) => $carry + $item, 0));
        } else {
            $chargeId .= sprintf('%\'.09d', (int) microtime(true));
        }

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
