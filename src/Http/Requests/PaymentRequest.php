<?php

namespace FriendsOfBotble\SePay\Http\Requests;

use Botble\Support\Http\Requests\Request;
use FriendsOfBotble\SePay\SePay;
use Illuminate\Validation\Rule;

class PaymentRequest extends Request
{
    public function rules(): array
    {
        return [
            'sepay_bank' => ['required', 'string', Rule::in(array_keys(SePay::getBanksList()))],
            'sepay_bank_account_number' => ['required', 'string'],
            'sepay_bank_account_holder' => ['required', 'string'],
            'sepay_pay_code_prefix' => ['required', 'string', 'alpha_num'],
        ];
    }
}
