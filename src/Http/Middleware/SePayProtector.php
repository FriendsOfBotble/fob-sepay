<?php

namespace FriendsOfBotble\SePay\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SePayProtector
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('Authorization');
        $apiKey = ! empty($apiKey) ? Str::after($apiKey, 'Apikey ') : false;
        $hashedKey = get_payment_setting('webhook_secret', SEPAY_PAYMENT_METHOD_NAME);

        if (! $apiKey || ! Hash::check($apiKey, $hashedKey)) {
            return new JsonResponse([
                'success' => 'false',
                'message' => 'api key invalid.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
