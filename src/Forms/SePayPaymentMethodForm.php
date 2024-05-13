<?php

namespace FriendsOfBotble\SePay\Forms;

use Botble\Base\Facades\Assets;
use Botble\Base\Forms\FieldOptions\HtmlFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Payment\Forms\PaymentMethodForm;
use FriendsOfBotble\SePay\Http\Requests\PaymentRequest;
use FriendsOfBotble\SePay\SePay;

class SePayPaymentMethodForm extends PaymentMethodForm
{
    public function setup(): void
    {
        parent::setup();

        Assets::addScriptsDirectly('vendor/core/plugins/fob-sepay/js/settings.js');

        $this
            ->setValidatorClass(PaymentRequest::class)
            ->paymentId(SEPAY_PAYMENT_METHOD_NAME)
            ->paymentName('SePay')
            ->paymentDescription('Thanh toán chuyển khoản ngân hàng với QR Code. Tự động xác nhận thanh toán bởi SePay.')
            ->paymentLogo(url('vendor/core/plugins/fob-sepay/images/sepay.png'))
            ->paymentUrl('https://sepay.vn')
            ->add(
                get_payment_setting_key('bank', SEPAY_PAYMENT_METHOD_NAME),
                SelectField::class,
                SelectFieldOption::make()
                    ->searchable()
                    ->choices(SePay::getBanksList())
                    ->selected(get_payment_setting('bank', SEPAY_PAYMENT_METHOD_NAME))
                    ->label('Ngân hàng')
                    ->toArray()
            )
            ->add(
                get_payment_setting_key('account_number', SEPAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label('Số tài khoản')
                    ->value(get_payment_setting('account_number', SEPAY_PAYMENT_METHOD_NAME))
                    ->toArray()
            )
            ->add(
                get_payment_setting_key('account_holder', SEPAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label('Chủ tài khoản')
                    ->value(get_payment_setting('account_holder', SEPAY_PAYMENT_METHOD_NAME))
                    ->toArray()
            )
            ->add(
                get_payment_setting_key('prefix', SEPAY_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->value(get_payment_setting('prefix', SEPAY_PAYMENT_METHOD_NAME, 'SDH'))
                    ->label('Tiền tố mã thanh toán')
                    ->helperText('Chỉ được phép chứa chữ cái và số, không dấu và không khoảng trắng. Ví dụ: SDH')
            )
            ->add(
                get_payment_setting_key('webhook_secret', SEPAY_PAYMENT_METHOD_NAME),
                HtmlField::class,
                HtmlFieldOption::make()
                    ->view('plugins/fob-sepay::webhook-secret')
            )
        ;
    }
}
