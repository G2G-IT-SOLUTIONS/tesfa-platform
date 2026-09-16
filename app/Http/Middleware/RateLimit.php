<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimit
{
    public function handle(Request $request, Closure $next, string $key = 'api', int $maxAttempts = 60): Response
    {
        $limiterKey = $key . ':' . ($request->user()?->id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            return response()->json(['message' => 'Too many requests.'], 429);
        }

        RateLimiter::hit($limiterKey, 60);

        return $next($request);
    }
}
