<?php

namespace FriendsOfBotble\SePay\Forms;

use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Payment\Forms\PaymentMethodForm;
use FriendsOfBotble\SePay\Http\Requests\PaymentRequest;
use FriendsOfBotble\SePay\SePayClient;

class SePayPaymentMethodForm extends PaymentMethodForm
{
    public function setup(): void
    {
        $client = new SePayClient();

        $this
            ->template('plugins/fob-sepay::forms.payment-method')
            ->setValidatorClass(PaymentRequest::class)
            ->paymentId(SEPAY_PAYMENT_METHOD_NAME)
            ->paymentName('SePay')
            ->paymentDescription('Thanh toán chuyển khoản ngân hàng với QR Code. Tự động xác nhận thanh toán bởi SePay.')
            ->paymentLogo(url('vendor/core/plugins/fob-sepay/images/sepay.png'))
            ->paymentUrl('https://sepay.vn')
            ->when($client->isConnected(), function (PaymentMethodForm $form) use ($client) {
                $bankAccounts = collect($client->bankAccounts())
                    ->mapWithKeys(fn($item) => [
                        $item['id'] => $item['bank']['short_name'] . ' - ' . $item['account_number'] . ' - ' . $item['account_holder_name'],
                    ])->all();

                $paymentCodePrefixes = collect($client->company()->configurations['payment_code_formats'])
                    ->reject(fn($item) => ! $item['is_active'])
                    ->mapWithKeys(fn($item) => [
                        $item['prefix'] => $item['prefix'],
                    ])->all();

                $bankAccountId = get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME);

                if ($bankAccountId) {
                    $bankSubAccounts = collect($client->bankSubAccounts($bankAccountId))
                        ->mapWithKeys(fn($item) => [
                            $item['id'] => "{$item['account_number']}" . ($item['account_holder_name'] ? " - {$item['account_holder_name']}" : ''),
                        ])->all();
                } else {
                    $bankSubAccounts = [];
                }

                $form
                    ->add(
                        get_payment_setting_key('bank_account_id', SEPAY_PAYMENT_METHOD_NAME),
                        SelectField::class,
                        SelectFieldOption::make()
                            ->searchable()
                            ->choices($bankAccounts)
                            ->selected(get_payment_setting('bank_account_id', SEPAY_PAYMENT_METHOD_NAME))
                            ->label('Tài khoản ngân hàng')
                    )
                    ->add(
                        get_payment_setting_key('bank_sub_account_id', SEPAY_PAYMENT_METHOD_NAME),
                        SelectField::class,
                        SelectFieldOption::make()
                            ->searchable()
                            ->choices(empty($bankSubAccounts) ? [
                                '' => '-- Chọn tài khoản ảo --',
                            ] : $bankSubAccounts)
                            ->selected(get_payment_setting('bank_sub_account_id', SEPAY_PAYMENT_METHOD_NAME))
                            ->label('Tài khoản ảo')
                    )
                    ->add(
                        get_payment_setting_key('prefix', SEPAY_PAYMENT_METHOD_NAME),
                        SelectField::class,
                        SelectFieldOption::make()
                            ->choices($paymentCodePrefixes)
                            ->selected(get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SDH'))
                            ->label('Tiền tố mã thanh toán')
                    );
            });
    }
}
