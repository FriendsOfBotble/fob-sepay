@if (setting('payment_sepay_status') == 1)
    <x-plugins-payment::payment-method
        :name="SEPAY_PAYMENT_METHOD_NAME"
        paymentName="SePay"
    />
@endif
