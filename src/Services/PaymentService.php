<?php

namespace FriendsOfBotble\SePay\Services;

use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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

    /**
     * A multi-vendor checkout stores one payment per order under the same charge ID,
     * but the customer makes a single transfer for all of them. Rows that are no longer
     * pending (e.g. the order was cancelled) are not part of that transfer.
     */
    public function pendingPaymentsByChargeId(string $chargeId): Builder
    {
        return $this->paymentsByChargeId($chargeId)->where('status', PaymentStatusEnum::PENDING);
    }

    /**
     * The payment that represents the whole charge. The charge awaits payment while any row
     * is pending, and is paid once none is pending and one is completed, so a cancelled
     * vendor order can't hide the state of the others.
     */
    public function chargeStatusPayment(string $chargeId): ?Payment
    {
        $payments = $this->paymentsByChargeId($chargeId)->oldest('id')->get();

        return $payments->first(fn (Payment $payment) => $payment->status == PaymentStatusEnum::PENDING)
            ?? $payments->first(fn (Payment $payment) => $payment->status == PaymentStatusEnum::COMPLETED)
            ?? $payments->first();
    }

    protected function paymentsByChargeId(string $chargeId): Builder
    {
        return Payment::query()
            ->where('payment_channel', SEPAY_PAYMENT_METHOD_NAME)
            ->where('charge_id', $chargeId);
    }

    /**
     * Ecommerce stores order payments in the default currency even when the customer pays
     * in another one (and vendor subscriptions in the visitor's), so each row is converted
     * from its own currency. Returns null when the total can't be expressed in VND.
     */
    public function totalInVnd(Collection $payments): ?float
    {
        $total = 0.0;

        foreach ($payments as $payment) {
            $amount = $this->convertToVnd((float) $payment->amount, (string) $payment->currency);

            if ($amount === null) {
                return null;
            }

            $total += $amount;
        }

        return $total > 0 ? round($total) : null;
    }

    public function hasConvertedAmount(Collection $payments): bool
    {
        return $payments->contains(fn (Payment $payment) => strtoupper((string) $payment->currency) !== 'VND');
    }

    protected function convertToVnd(float $amount, string $currency): ?float
    {
        if (strtoupper($currency) === 'VND') {
            return $amount;
        }

        if (! function_exists('get_all_currencies')) {
            return null;
        }

        $currencies = get_all_currencies();
        $findCurrency = fn (string $title) => $currencies->first(
            fn ($item) => strtoupper((string) $item->title) === strtoupper($title)
        );

        $from = $findCurrency($currency);
        $vnd = $findCurrency('VND');

        if (! $from || ! $vnd || (float) $from->exchange_rate <= 0 || (float) $vnd->exchange_rate <= 0) {
            return null;
        }

        // Exchange rates are relative to the default currency.
        return $amount / (float) $from->exchange_rate * (float) $vnd->exchange_rate;
    }
}
