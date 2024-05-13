<?php

use Botble\Base\Facades\AdminHelper;
use FriendsOfBotble\SePay\Http\Middleware\SePayProtector;
use Illuminate\Support\Facades\Route;

Route::post('sepay/webhook', [
    'uses' => 'FriendsOfBotble\SePay\Http\Controllers\WebhookController@__invoke',
    'as' => 'sepay.webhook',
    'middleware' => [SePayProtector::class],
]);

Route::post('sepay/transactions/check', [
    'uses' => 'FriendsOfBotble\SePay\Http\Controllers\TransactionCheckerController@__invoke',
    'as' => 'sepay.transactions.check',
]);

Route::group(['namespace' => 'FriendsOfBotble\SePay\Http\Controllers'], function () {
    AdminHelper::registerRoutes(function () {
        Route::post('sepay/webhook-secret', [
            'uses' => 'WebhookSecretGeneratorController@__invoke',
            'as' => 'sepay.webhook-secret.generate',
        ]);
    });
});
