<?php

namespace FriendsOfBotble\SePay\Http\Controllers;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use FriendsOfBotble\SePay\Http\Requests\WebhookRequest;
use FriendsOfBotble\SePay\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WebhookController
{
    /**
     * Underpayment accepted, in VND, to absorb bank rounding.
     */
    protected const AMOUNT_TOLERANCE = 1000;

    /**
     * Relative tolerance for amounts converted from another currency, matching core's underpayment check.
     */
    protected const CONVERTED_AMOUNT_TOLERANCE_RATE = 0.01;

    public function __invoke(WebhookRequest $request, PaymentService $paymentService): JsonResponse
    {
        do_action('payment_before_making_api_request', SEPAY_PAYMENT_METHOD_NAME, []);

        $content = (string) $request->input('content', '');
        $code = (string) $request->input('code', '');
        $transferAmount = (float) $request->input('transferAmount');

        $payments = Payment::query()
            ->where('payment_channel', SEPAY_PAYMENT_METHOD_NAME)
            // An empty charge_id would turn the LIKE below into '%%' and match every transfer.
            ->whereNotNull('charge_id')
            ->where('charge_id', '!=', '');

        // SePay detects the payment code itself and sends it as `code`; fall back to the transfer content.
        $payment = $code !== ''
            ? (clone $payments)->where('charge_id', $code)->first()
            : null;

        $payment ??= $payments
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

        $pendingPayments = $paymentService->pendingPaymentsByChargeId((string) $payment->charge_id)->get();

        // Already processed (SePay retries webhooks).
        if ($pendingPayments->isEmpty()) {
            return response()->json(['success' => true]);
        }

        $expectedAmount = $paymentService->totalInVnd($pendingPayments);

        if ($expectedAmount === null) {
            return response()->json([
                'success' => false,
                'message' => 'unable to convert payment amount to VND.',
            ], 400);
        }

        // A converted amount moves with the exchange rate between showing the QR and the transfer.
        $tolerance = $paymentService->hasConvertedAmount($pendingPayments)
            ? max(static::AMOUNT_TOLERANCE, $expectedAmount * static::CONVERTED_AMOUNT_TOLERANCE_RATE)
            : static::AMOUNT_TOLERANCE;

        if ($transferAmount < ($expectedAmount - $tolerance)) {
            return response()->json([
                'success' => false,
                'message' => 'insufficient amount.',
            ], 400);
        }

        // Lock by primary key: charge_id isn't indexed, so a locking read on it would lock the whole table.
        $completedPayments = DB::transaction(function () use ($pendingPayments, $request) {
            $lockedPayments = Payment::query()
                ->whereKey($pendingPayments->modelKeys())
                ->lockForUpdate()
                ->get()
                ->filter(fn (Payment $item) => $item->status == PaymentStatusEnum::PENDING);

            foreach ($lockedPayments as $lockedPayment) {
                $lockedPayment->update([
                    'status' => PaymentStatusEnum::COMPLETED,
                    'metadata' => $request->input(),
                ]);
            }

            return $lockedPayments;
        });

        if ($completedPayments->isEmpty()) {
            return response()->json(['success' => true]);
        }

        // Core finalizes every order in a single call, as it does for other gateways on multi-vendor checkouts.
        // Vendor subscription payments have no order, so order_id stays empty for their listener.
        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
            'charge_id' => $payment->charge_id,
            'order_id' => $completedPayments->pluck('order_id')->filter()->values()->all(),
            'customer_id' => $payment->customer_id,
            'customer_type' => $payment->customer_type,
            'payment_channel' => $payment->payment_channel?->getValue(),
            'status' => PaymentStatusEnum::COMPLETED,
            'amount' => $completedPayments->sum('amount'),
        ], $request);

        do_action('payment_after_api_response', SEPAY_PAYMENT_METHOD_NAME, [], $request->all());

        return response()->json(['success' => true]);
    }
}
