<?php

use Botble\Base\Facades\AdminHelper;
use FriendsOfBotble\SePay\Http\Controllers\OAuthController;
use FriendsOfBotble\SePay\Http\Controllers\SePayController;
use FriendsOfBotble\SePay\Http\Controllers\WebhookController;
use FriendsOfBotble\SePay\Http\Middleware\SePayProtector;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::prefix('sepay')->name('sepay.')->group(function () {
    Route::get('oauth/callback', [OAuthController::class, 'getCallback'])
        ->name('oauth.callback');

    Route::post('oauth/callback', [OAuthController::class, 'callback'])
        ->withoutMiddleware(VerifyCsrfToken::class)
        ->middleware('throttle:10,1');

    Route::post('webhook', [WebhookController::class, '__invoke'])
        ->name('webhook')
        ->middleware(SePayProtector::class);

    Route::post('transactions/check', [SePayController::class, 'checkTransaction'])
        ->name('transactions.check');

    AdminHelper::registerRoutes(function () {
        Route::group(['permission' => 'payments.settings'], function () {
            Route::get('oauth/connect', [OAuthController::class, 'connect'])
                ->name('oauth.connect');

            Route::post('oauth/disconnect', [OAuthController::class, 'disconnect'])
                ->name('oauth.disconnect');

            Route::get('bank-sub-accounts', [SePayController::class, 'bankSubAccounts'])
                ->name('bank-sub-accounts');

            Route::get('payment-codes', [SePayController::class, 'paymentCodes'])
                ->name('payment-codes');
        });
    });
});
