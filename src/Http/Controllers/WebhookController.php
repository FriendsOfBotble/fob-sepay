<?php

namespace FriendsOfBotble\SePay\Http\Controllers;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use FriendsOfBotble\SePay\Http\Requests\WebhookRequest;
use Illuminate\Http\JsonResponse;

class WebhookController
{
    public function __invoke(WebhookRequest $request): JsonResponse
    {
        do_action('payment_before_making_api_request', SEPAY_PAYMENT_METHOD_NAME, []);

        $payment = Payment::query()
            ->where('charge_id', $request->input('code'))
            ->where('payment_channel', SEPAY_PAYMENT_METHOD_NAME)
            ->where('amount', $request->input('transferAmount'))
            ->first();

        if (! $payment) {
            return response()->json(['success' => false]);
        }

        do_action('payment_before_making_api_request', SEPAY_PAYMENT_METHOD_NAME, []);

        if ($payment->status == PaymentStatusEnum::COMPLETED) {
            return response()->json(['success' => true]);
        }

        $payment->update([
            'status' => PaymentStatusEnum::COMPLETED,
            'metadata' => $request->input(),
        ]);

        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'charge_id' => $payment->charge_id,
            'order_id' => $payment->order_id,
            'status' => PaymentStatusEnum::COMPLETED,
            'amount' => $payment->amount,
        ], $request);

        do_action('payment_after_api_response', SEPAY_PAYMENT_METHOD_NAME, [], $request->all());

        return response()->json(['success' => true]);
    }
}
