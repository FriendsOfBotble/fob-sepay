<?php

namespace FriendsOfBotble\SePay\Providers;

use Botble\Base\Facades\BaseHelper;
use Botble\Ecommerce\Models\Order;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Facades\PaymentMethods;
use Botble\Payment\Http\Requests\PaymentMethodRequest;
use Exception;
use FriendsOfBotble\SePay\Forms\SePayPaymentMethodForm;
use FriendsOfBotble\SePay\SePayClient;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rule;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data): ?string {
            PaymentMethods::method(SEPAY_PAYMENT_METHOD_NAME, [
                'html' => view('plugins/fob-sepay::payment-method', $data)->render(),
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

            $chargeId = get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SDH');
            $chargeId .= sprintf('%\'.09d', (int) (microtime(true) * 10));

            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'charge_id' => $chargeId,
                'order_id' => $paymentData['order_id'],
                'customer_id' => $paymentData['customer_id'],
                'customer_type' => $paymentData['customer_type'],
                'payment_channel' => SEPAY_PAYMENT_METHOD_NAME,
                'status' => PaymentStatusEnum::PENDING,
            ]);

            $data['charge_id'] = $chargeId;

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
                || $payment->payment_channel != SEPAY_PAYMENT_METHOD_NAME
                || $payment->currency !== 'VND'
            ) {
                return $html;
            }

            $orderAmount = 0;

            foreach ($orders as $item) {
                $orderAmount += $item->amount;
            }

            $banks = [
                'vietcombank' => 'Vietcombank',
                'vpbank' => 'VPBank',
                'acb' => 'ACB',
                'sacombank' => 'Sacombank',
                'hdbank' => 'HDBank',
                'vietinbank' => 'VietinBank',
                'techcombank' => 'Techcombank',
                'mbbank' => 'MBBank',
                'bidv' => 'BIDV',
                'msb' => 'MSB',
                'shinhanbank' => 'ShinhanBank',
                'tpbank' => 'TPBank',
                'eximbank' => 'Eximbank',
                'vib' => 'VIB',
                'agribank' => 'Agribank',
                'publicbank' => 'PublicBank',
                'kienlongbank' => 'KienLongBank',
                'ocb' => 'OCB',
            ];

            $chargeId = $payment->charge_id;
            $bank = $banks[get_payment_setting('bank', SEPAY_PAYMENT_METHOD_NAME)] ?? 'Vietcombank';
            $bankAccountNumber = get_payment_setting('account_number', SEPAY_PAYMENT_METHOD_NAME);
            $bankAccountHolder = get_payment_setting('account_holder', SEPAY_PAYMENT_METHOD_NAME);
            $bankShortName = $bank;

            $client = new SePayClient();

            if ($client->isConnected()) {
                $bankAccount = $client->bankAccount(get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME));

                $bank = match (get_payment_setting('bank_display', SEPAY_PAYMENT_METHOD_NAME, 'short_name')) {
                    'full_name' => $bankAccount->bank['full_name'],
                    'short_name' => $bankAccount->bank['short_name'],
                    'full_name_short_name' => "{$bankAccount->bank['full_name']} ({$bankAccount->bank['short_name']})",
                    default => $bank,
                };

                $bankShortName = $bankAccount->bank['short_name'];
                $bankAccountNumber = $bankAccount->account_number;
                $bankAccountHolder = $bankAccount->account_holder_name;

                if ($bankSubAccountId = get_payment_setting('bank_sub_account_id', SEPAY_PAYMENT_METHOD_NAME)) {
                    $bankSubAccounts = $client->bankSubAccounts($bankAccount->id);

                    $bankSubAccount = collect($bankSubAccounts)
                        ->where('bank_account_id', $bankAccount->id)
                        ->where('id', $bankSubAccountId)
                        ->first();

                    if ($bankSubAccount) {
                        $bankAccountNumber = $bankSubAccount['account_number'];
                        $bankAccountHolder = $bankSubAccount['account_holder_name'] ?: $bankAccountHolder;
                    }
                }
            }

            $bankLogo = $bankAccount->bank['logo_url'];
            $qrCodeUrl = $client->getQrCodeUrl($bankAccountNumber, $bankShortName, $orderAmount, $chargeId);

            return $html .= view('plugins/fob-sepay::bank-info', compact(
                'orderAmount',
                'qrCodeUrl',
                'bank',
                'bankLogo',
                'bankAccountNumber',
                'bankAccountHolder',
                'chargeId',
                'payment'
            ))->render();
        }, 9999, 2);

        add_filter('core_request_rules', function (array $rules, Request $request) {
            if ($request instanceof PaymentMethodRequest) {
                $client = new SePayClient();

                $bankAccounts = $client->bankAccounts();

                $rules = [
                    ...$rules,
                    'payment_sepay_bank_account_id' => ['required', 'string', Rule::in(array_column($bankAccounts, 'id'))],
                    'payment_sepay_bank_sub_account_id' => [
                        Rule::requiredIf(function () use ($request, $bankAccounts) {
                            $selectedBankAccount = collect($bankAccounts)->firstWhere('id', $request->input('payment_sepay_bank_account_id'));
                            $requiredBanks = ['BIDV', 'MSB', 'KienLongBank', 'OCB'];

                            return $selectedBankAccount && in_array($selectedBankAccount['bank']['short_name'], $requiredBanks);
                        }),
                        'nullable',
                        'string',
                        fn () => Rule::in(array_column($client->bankSubAccounts($request->get('payment_sepay_bank_account_id')), 'id')),
                    ],
                    'payment_sepay_prefix' => [
                        'required',
                        'string',
                        Rule::in(array_column(Arr::get($client->company(), 'configurations.payment_code_formats'), 'prefix')),
                    ],
                ];
            }

            return $rules;
        }, 999, 2);

        add_action('core_after_update_settings', function (array $data) {
            if (! array_key_exists('payment_sepay_status', $data)) {
                return;
            }

            if (! $bankAccountId = get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME)) {
                return;
            }

            $client = new SePayClient();
            $webhookId = setting()->get('sepay_webhook_id');

            $data = [
                'bank_account_id' => $bankAccountId,
            ];

            try {
                if ($webhookId) {
                    $webhook = $client->webhook($webhookId);

                    if (! $webhook) {
                        $webhook = $client->createWebhook($data);
                    } elseif ($webhook['bank_account_id'] != $bankAccountId) {
                        $webhook = $client->updateWebhook($webhookId, $data);
                        $webhook['id'] = $webhookId;
                    }
                } else {
                    $webhook = $client->createWebhook($data);
                    dd($webhook);
                }
            } catch (Exception $e) {
                if ($e->getCode() === 404) {
                    $webhook = $client->createWebhook($data);
                } else {
                    BaseHelper::logError($e);
                }
            }

            setting()->set('sepay_webhook_id', $webhook['id']);
        }, 999);
    }
}
