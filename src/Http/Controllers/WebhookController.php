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

        $content = $request->input('content', '');
        $transferAmount = (float) $request->input('transferAmount');

        $payment = Payment::query()
            ->where('payment_channel', SEPAY_PAYMENT_METHOD_NAME)
            ->where(function ($query) use ($content) {
                $query->whereRaw('? LIKE CONCAT("%", charge_id, "%")', [$content])
                    ->orWhere('charge_id', $content);
            })
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'payment not found.',
            ], 400);
        }

        $expectedAmount = $payment->amount;

        if ($payment->currency !== 'VND') {
            $vndCurrency = get_all_currencies()->firstWhere('title', 'VND');

            if ($vndCurrency) {
                $expectedAmount = round($payment->amount * $vndCurrency->exchange_rate);
            }
        }

        $tolerance = 1000;

        if ($transferAmount < ($expectedAmount - $tolerance)) {
            return response()->json([
                'success' => false,
                'message' => 'insufficient amount.',
            ], 400);
        }

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
            'customer_id' => $payment->customer_id,
            'customer_type' => $payment->customer_type,
            'payment_channel' => $payment->payment_channel?->getValue(),
            'status' => PaymentStatusEnum::COMPLETED,
            'amount' => $payment->amount,
        ], $request);

        do_action('payment_after_api_response', SEPAY_PAYMENT_METHOD_NAME, [], $request->all());

        return response()->json(['success' => true]);
    }
}
