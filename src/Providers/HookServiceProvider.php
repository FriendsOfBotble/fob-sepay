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
use FriendsOfBotble\SePay\Services\BankService;
use FriendsOfBotble\SePay\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rule;

class HookServiceProvider extends ServiceProvider
{
    protected BankService $bankService;
    protected PaymentService $paymentService;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->bankService = new BankService();
        $this->paymentService = new PaymentService();
    }

    public function boot(): void
    {
        $this->registerPaymentMethod();
        $this->registerPaymentSettings();
        $this->registerPaymentInfoDetail();
        $this->registerPaymentMethodEnum();
        $this->registerAfterPostCheckout();
        $this->registerThankYouCustomerInfo();
        $this->registerRequestRules();
        $this->registerAfterUpdateSettings();
    }

    protected function registerPaymentMethod(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data): ?string {
            PaymentMethods::method(SEPAY_PAYMENT_METHOD_NAME, [
                'html' => view('plugins/fob-sepay::payment-method', $data)->render(),
            ]);

            return $html;
        }, 999, 2);
    }

    protected function registerPaymentSettings(): void
    {
        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $settings): string {
            return $settings . SePayPaymentMethodForm::create()->renderForm();
        }, 999);
    }

    protected function registerPaymentInfoDetail(): void
    {
        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == SEPAY_PAYMENT_METHOD_NAME && $payment->metadata) {
                return view('plugins/fob-sepay::detail', compact('payment'));
            }

            return $data;
        }, 999, 2);
    }

    protected function registerPaymentMethodEnum(): void
    {
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
    }

    protected function registerAfterPostCheckout(): void
    {
        add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, function (array $data, Request $request): array {
            if ($data['type'] !== SEPAY_PAYMENT_METHOD_NAME) {
                return $data;
            }

            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);
            $chargeId = $this->paymentService->generateChargeId();

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
    }

    protected function registerThankYouCustomerInfo(): void
    {
        add_filter('ecommerce_thank_you_customer_info', function (?string $html, Collection|Order $orders) {
            if (! $orders instanceof Collection) {
                $collection = new Collection();
                $collection->add($orders);
                $orders = $collection;
            }

            $payment = $orders->first()->payment;

            if (! $this->isValidPayment($payment)) {
                return $html;
            }

            if ($payment->status == PaymentStatusEnum::PENDING) {
                $orderAmount = $this->calculateOrderAmount($orders);
                $bankInfo = $this->bankService->getBankInfo();
                $qrCodeUrl = $this->bankService->getQrCodeUrl($bankInfo['bankAccountNumber'], $bankInfo['bankShortName'], $orderAmount, $payment->charge_id);

                $html .= view('plugins/fob-sepay::bank-info', [
                    'orderAmount' => $orderAmount,
                    'qrCodeUrl' => $qrCodeUrl,
                    'payment' => $payment,
                    'bankInfo' => $bankInfo,
                    'chargeId' => $payment->charge_id,
                ])->render();
            }

            return $html;
        }, 9999, 2);
    }

    protected function registerRequestRules(): void
    {
        add_filter('core_request_rules', function (array $rules, Request $request) {
            if ($request instanceof PaymentMethodRequest && $request->post('type') === SEPAY_PAYMENT_METHOD_NAME) {
                $rules = array_merge($rules, $this->getPaymentMethodRules($request));
            }

            return $rules;
        }, 999, 2);
    }

    protected function registerAfterUpdateSettings(): void
    {
        add_action('core_after_update_settings', function (array $data) {
            if (! array_key_exists('payment_sepay_status', $data)) {
                return;
            }

            $this->updateWebhookSettings();
            $this->updateBankInfo();
        }, 999);
    }

    protected function isValidPayment($payment): bool
    {
        return $payment
            && $payment->payment_channel == SEPAY_PAYMENT_METHOD_NAME
            && $payment->currency === 'VND';
    }

    protected function calculateOrderAmount(Collection $orders): float
    {
        return $orders->sum('amount');
    }

    protected function getPaymentMethodRules(Request $request): array
    {
        try {
            $client = new SePayClient();
            $bankAccounts = $client->bankAccounts();

            return [
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
        } catch (Exception) {
            return [
                'payment_sepay_bank_account_id' => ['required', 'string'],
                'payment_sepay_bank_sub_account_id' => ['nullable', 'string'],
                'payment_sepay_prefix' => ['required', 'string'],
            ];
        }
    }

    protected function updateBankInfo(): void
    {
        try {
            $client = new SePayClient();
            if ($client->isConnected()) {
                $bankAccount = $client->bankAccount(get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME));

                $bank = match (get_payment_setting('bank_display', SEPAY_PAYMENT_METHOD_NAME, 'short_name')) {
                    'full_name' => $bankAccount->bank['full_name'],
                    'short_name' => $bankAccount->bank['brand_name'],
                    'full_name_short_name' => "{$bankAccount->bank['full_name']} ({$bankAccount->bank['brand_name']})",
                    default => $bankAccount->bank['brand_name'],
                };

                $bankShortName = $bankAccount->bank['short_name'];
                $bankBrandName = $bankAccount->bank['brand_name'];
                $bankAccountNumber = $bankAccount->account_number;
                $bankAccountHolder = $bankAccount->account_holder_name;
                $bankLogo = $bankAccount->bank['logo_url'];

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

                setting()->set([
                    'payment_sepay_bank' => $bank,
                    'payment_sepay_bank_short_name' => $bankShortName,
                    'payment_sepay_bank_brand_name' => $bankBrandName,
                    'payment_sepay_bank_account_number' => $bankAccountNumber,
                    'payment_sepay_bank_account_holder' => $bankAccountHolder,
                    'payment_sepay_bank_logo' => $bankLogo,
                ])->save();
            }
        } catch (Exception $e) {
            BaseHelper::logError($e);
        }
    }

    protected function updateWebhookSettings(): void
    {
        if (! $bankAccountId = get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME)) {
            return;
        }

        $client = new SePayClient();
        $webhookId = setting()->get('sepay_webhook_id');

        $data = [
            'bank_account_id' => $bankAccountId,
        ];

        try {
            $webhook = $this->handleWebhookUpdate($client, $webhookId, $data);
            setting()->set('sepay_webhook_id', $webhook['id'])->save();
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                $webhook = $client->createWebhook($data);
                setting()->set('sepay_webhook_id', $webhook['id'])->save();
            } else {
                BaseHelper::logError($e);
            }
        }
    }

    protected function handleWebhookUpdate(SePayClient $client, ?string $webhookId, array $data): array
    {
        if (! $webhookId) {
            return $client->createWebhook($data);
        }

        $webhook = $client->webhook($webhookId);

        if (! $webhook) {
            return $client->createWebhook($data);
        }

        if ($webhook['bank_account_id'] != $data['bank_account_id']) {
            $webhook = $client->updateWebhook($webhookId, $data);
            $webhook['id'] = $webhookId;
        }

        return $webhook;
    }
}
