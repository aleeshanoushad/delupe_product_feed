<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('services.api.key');
        $providedKey = $request->header('X-API-Key');

        if (! $apiKey || ! $providedKey || ! hash_equals($apiKey, $providedKey)) {
            return response()->json(['message' => 'Unauthorized. Invalid or missing API key.'], 401);
        }

        return $next($request);
    }
}
