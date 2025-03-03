<?php

use Botble\Base\Facades\AdminHelper;
use FriendsOfBotble\SePay\Http\Controllers\OAuthController;
use FriendsOfBotble\SePay\Http\Controllers\SePayController;
use FriendsOfBotble\SePay\Http\Middleware\SePayProtector;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('sepay/oauth/connect', [OAuthController::class, 'connect'])->name('sepay.oauth.connect');

Route::get('sepay/oauth/callback', [OAuthController::class, 'getCallback'])
    ->name('sepay.oauth.callback');
Route::post('sepay/oauth/callback', [OAuthController::class, 'callback'])
    ->withoutMiddleware(VerifyCsrfToken::class);
Route::post('sepay/oauth/disconnect', [OAuthController::class, 'disconnect'])
    ->name('sepay.oauth.disconnect');

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
        Route::get('sepay/bank-sub-accounts', [SePayController::class, 'bankSubAccounts'])
            ->name('sepay.bank-sub-accounts');

        Route::get('sepay/payment-codes', [SePayController::class, 'paymentCodes'])
            ->name('sepay.payment-codes');
    });
});
