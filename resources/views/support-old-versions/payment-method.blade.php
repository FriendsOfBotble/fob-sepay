@php
    $name = SEPAY_PAYMENT_METHOD_NAME;
    $isSelected = PaymentMethods::getSelectingMethod() === $name;
    $id = sprintf('payment-%s', $name);
@endphp

<li class="list-group-item">
    <input
        class="magic-radio js_payment_method"
        id="{{ $id }}"
        name="payment_method"
        type="radio"
        value="{{ $name }}"
        @checked($isSelected)
    >
    <label for="{{ $id }}" class="form-label fw-medium">
        {{ get_payment_setting('name', $name, trans('plugins/payment::payment.payment_via_card')) }}
    </label>

    <div @class(['payment_collapse_wrap collapse mt-1', 'show' => $isSelected])>
        <p class="text-muted">{!! BaseHelper::clean(get_payment_setting('description', $name)) !!}</p>

        @if (! empty($supportedCurrencies) && ! in_array(get_application_currency()->title, $supportedCurrencies) && ! get_application_currency()->replicate()->newQuery()->whereIn('title', $supportedCurrencies)->exists())
            @php
                $currencies = get_all_currencies()->filter(fn ($item) => in_array($item->title, $supportedCurrencies));
            @endphp

            <div class="alert alert-warning mt-3">
                {{ __(":name doesn't support :currency. List of currencies supported by :name: :currencies.", ['name' => \FriendsOfBotble\MercadoPago\Facades\MercadoPagoPayment::getDisplayName(), 'currency' => get_application_currency()->title, 'currencies' => implode(', ', $supportedCurrencies)]) }}

                {{ $currencyNotSupportedMessage ?? '' }}

                @if ($currencies->isNotEmpty())
                    <div>
                        {{ __('Please switch currency to any supported currency') }}:&nbsp;&nbsp;
                        @foreach ($currencies as $currency)
                            <a
                                href="{{ route('public.change-currency', $currency->title) }}"
                                @class(['active' => get_application_currency_id() === $currency->getKey()])
                            >
                                {{ $currency->title }}
                            </a>
                            @if (!$loop->last)
                                &nbsp; | &nbsp;
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</li>
