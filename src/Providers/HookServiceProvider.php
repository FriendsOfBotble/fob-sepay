<?php

namespace FriendsOfBotble\SePay\Providers;

use Botble\Ecommerce\Models\Order;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Payment\Http\Requests\PaymentMethodRequest;
use FriendsOfBotble\SePay\Forms\SePayPaymentMethodForm;
use FriendsOfBotble\SePay\SePay;
use FriendsOfBotble\SePay\SePayClient;
use FriendsOfBotble\SePay\Services\Gateways\SePayPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rule;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data): ?string {
            if (! view()->exists('plugins/payment::components.payment-method')) {
                return $html . view('plugins/fob-sepay::support-old-versions.payment-method', $data)->render();
            }

            PaymentMethods::method(SEPAY_PAYMENT_METHOD_NAME, [
                'html' => view('plugins/fob-sepay::payments.methods', $data)->render(),
            ]);

            return $html;
        }, 999, 2);

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $settings): string {
            return $settings . SePayPaymentMethodForm::create()->renderForm();
        }, 999);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == SEPAY_PAYMENT_METHOD_NAME && $payment->metadata) {
                return view('plugins/fob-sepay::detail', compact('payment'));
            }

            return $data;
        }, 999, 2);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class == PaymentMethodEnum::class) {
                $values['SEPAY'] = SEPAY_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 999, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == SEPAY_PAYMENT_METHOD_NAME) {
                $value = 'SePay';
            }

            return $value;
        }, 999, 2);

        add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, function (array $data, Request $request): array {
            if ($data['type'] !== SEPAY_PAYMENT_METHOD_NAME) {
                return $data;
            }

            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

            $data['charge_id'] = (new SePayPaymentService())->execute($paymentData);

            return $data;
        }, 999, 2);

        add_filter('ecommerce_thank_you_customer_info', function (?string $html, Collection|Order $orders) {
            if (! $orders instanceof Collection) {
                $collection = new Collection();
                $collection->add($orders);
                $orders = $collection;
            }

            $payment = $orders->first()->payment;

            if (
                ! $payment
                || $payment->payment_channel->getValue() !== SEPAY_PAYMENT_METHOD_NAME
                || $payment->currency !== 'VND'
            ) {
                return $html;
            }

            $orderAmount = 0;

            foreach ($orders as $item) {
                $orderAmount += $item->amount;
            }

            $chargeId = $payment->charge_id;
            $bank = SePay::getBankById(get_payment_setting('bank', SEPAY_PAYMENT_METHOD_NAME));
            $bankAccountNumber = get_payment_setting('account_number', SEPAY_PAYMENT_METHOD_NAME);
            $bankAccountHolder = get_payment_setting('account_holder', SEPAY_PAYMENT_METHOD_NAME);

            $client = new SePayClient();

            if ($client->isConnected()) {
                $bankAccount = $client->bankAccount(get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME));

                $bank = "{$bankAccount->bank['full_name']} ({$bankAccount->bank['short_name']})";
                $bankAccountNumber = $bankAccount->account_number;
                $bankAccountHolder = $bankAccount->account_holder_name;

                if ($bankSubAccountId = get_payment_setting('bank_sub_account_id', SEPAY_PAYMENT_METHOD_NAME)) {
                    $bankSubAccounts = $client->bankSubAccounts($bankAccount->id);

                    $bankSubAccount = collect($bankSubAccounts)->firstWhere('id', $bankSubAccountId);

                    $bankAccountNumber = $bankSubAccount['account_number'];
                    $bankAccountHolder = $bankSubAccount['account_holder_name'];
                }
            }

            $html .= view(
                'plugins/fob-sepay::bank-info',
                [
                    'orderAmount' => $orderAmount,
                    'imageUrl' => SePay::getQRCodeUrl($orderAmount, $chargeId),
                    'bank' => $bank,
                    'bankAccountNumber' => $bankAccountNumber,
                    'bankAccountHolder' => $bankAccountHolder,
                    'chargeId' => $chargeId,
                    'payment' => $payment,
                ]
            )->render();

            return $html;
        }, 9999, 2);

        add_filter('core_request_rules', function (array $rules, Request $request) {
            if ($request instanceof PaymentMethodRequest) {
                $client = new SePayClient();

                $rules = [
                    ...$rules,
                    'payment_sepay_bank_account_id' => ['required', 'string', Rule::in(array_column($client->bankAccounts(), 'id'))],
                    'payment_sepay_bank_sub_account_id' => ['nullable', 'string', Rule::in(array_column($client->bankSubAccounts($request->get('payment_sepay_bank_account_id')), 'id'))],
                    'payment_sepay_prefix' => ['required', 'string', Rule::in(array_column($client->company()->configurations['payment_code_formats'], 'prefix'))],
                ];
            }

            return $rules;
        }, 999, 2);
    }
}
