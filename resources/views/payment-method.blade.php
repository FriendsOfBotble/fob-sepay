@if (setting('payment_sepay_status') == 1 && setting('payment_sepay_bank_account_number') && setting('payment_sepay_bank_short_name'))
    <x-plugins-payment::payment-method
        :name="SEPAY_PAYMENT_METHOD_NAME"
        paymentName="SePay"
    />
@endif
