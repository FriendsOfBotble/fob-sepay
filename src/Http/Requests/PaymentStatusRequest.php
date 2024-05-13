<?php

namespace FriendsOfBotble\SePay\Http\Requests;

use Botble\Support\Http\Requests\Request;

class PaymentStatusRequest extends Request
{
    public function rules(): array
    {
        return [
            'charge_id' => ['required', 'string', 'exists:payments'],
        ];
    }
}
