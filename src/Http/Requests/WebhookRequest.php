<?php

namespace FriendsOfBotble\SePay\Http\Requests;

use Botble\Support\Http\Requests\Request;

class WebhookRequest extends Request
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'gateway' => ['required', 'string'],
            'transactionDate' => ['required', 'string', 'date'],
            'accountNumber' => ['required', 'string'],
            'code' => ['required', 'string'],
            'content' => ['required', 'string'],
            'transferType' => ['required', 'string', 'in:in'],
            'transferAmount' => ['required', 'numeric'],
            'accumulated' => ['nullable', 'numeric'],
            'subAccount' => ['nullable', 'string'],
            'referenceCode' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }
}
