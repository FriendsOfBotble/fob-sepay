<?php

namespace FriendsOfBotble\SePay\Http\Controllers;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use FriendsOfBotble\SePay\Http\Requests\WebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WebhookController
{
    /**
     * Underpayment accepted, in VND, to absorb bank rounding.
     */
    protected const AMOUNT_TOLERANCE = 1000;

    public function __invoke(WebhookRequest $request): JsonResponse
    {
        do_action('payment_before_making_api_request', SEPAY_PAYMENT_METHOD_NAME, []);

        $content = $request->input('content', '');
        $transferAmount = (float) $request->input('transferAmount');

        $payment = Payment::query()
            ->where('payment_channel', SEPAY_PAYMENT_METHOD_NAME)
            ->where(function ($query) use ($content): void {
                $query
                    ->where('charge_id', $content)
                    ->orWhereRaw("? LIKE CONCAT('%', charge_id, '%')", [$content]);
            })
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'payment not found.',
            ], 400);
        }

        $expectedAmount = $this->expectedAmountInVnd($payment);

        if ($expectedAmount === null) {
            return response()->json([
                'success' => false,
                'message' => 'unable to convert payment amount to VND.',
            ], 400);
        }

        if ($transferAmount < ($expectedAmount - static::AMOUNT_TOLERANCE)) {
            return response()->json([
                'success' => false,
                'message' => 'insufficient amount.',
            ], 400);
        }

        // SePay retries webhooks, so lock the row to make sure the payment is only processed once.
        $processed = DB::transaction(function () use ($payment, $request): bool {
            $payment = Payment::query()->lockForUpdate()->find($payment->getKey());

            if (! $payment || $payment->status == PaymentStatusEnum::COMPLETED) {
                return false;
            }

            $payment->update([
                'status' => PaymentStatusEnum::COMPLETED,
                'metadata' => $request->input(),
            ]);

            return true;
        });

        if (! $processed) {
            return response()->json(['success' => true]);
        }

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

    protected function expectedAmountInVnd(Payment $payment): ?float
    {
        if (strtoupper((string) $payment->currency) === 'VND') {
            return (float) $payment->amount;
        }

        $vndCurrency = function_exists('get_all_currencies')
            ? get_all_currencies()->firstWhere('title', 'VND')
            : null;

        if (! $vndCurrency || (float) $vndCurrency->exchange_rate <= 0) {
            return null;
        }

        return round($payment->amount * $vndCurrency->exchange_rate);
    }
}
