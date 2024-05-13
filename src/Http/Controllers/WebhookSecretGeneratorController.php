<?php

namespace FriendsOfBotble\SePay\Http\Controllers;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Setting\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WebhookSecretGeneratorController extends SettingController
{
    public function __invoke()
    {
        $secret = Str::uuid()->toString();

        setting()->set(
            get_payment_setting_key('webhook_secret', SEPAY_PAYMENT_METHOD_NAME),
            Hash::make($secret)
        );

        setting()->save();

        return BaseHttpResponse::make()
            ->setData([
                'secret' => $secret,
            ])
            ->withCreatedSuccessMessage();
    }
}
