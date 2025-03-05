<?php

namespace FriendsOfBotble\SePay\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SePayProtector
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $this->apiToken($request);
        $storedApiKey = setting('sepay_api_key');

        if (
            ! $apiKey
            || ! $storedApiKey
            || ! hash_equals($storedApiKey, $apiKey)
        ) {
            return response()->json(['message' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    public function apiToken(Request $request): string
    {
        $header = $request->header('Authorization', '');

        if (! str_contains($header, 'Apikey ')) {
            return false;
        }

        $apiKey = str_replace('Apikey ', '', $header);

        return trim($apiKey);
    }
}
