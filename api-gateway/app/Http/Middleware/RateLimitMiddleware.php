<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * IP-based rate limiter.
 *
 * Limits each IP to RATE_LIMIT_PER_MINUTE requests per minute.
 * Returns a 429 Too Many Requests response with Retry-After header.
 */
final class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key      = 'gateway:' . $request->ip();
        $maxAttempts = (int) config('gateway.rate_limit_per_minute', 60);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message'     => 'Too many requests.',
                'retry_after' => $seconds,
            ], 429)->header('Retry-After', (string) $seconds);
        }

        RateLimiter::hit($key, 60); // Decay in 60 seconds

        return $next($request);
    }
}
