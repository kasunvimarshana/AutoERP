<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Core\Http\Responses\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $limit = '60,1'): Response
    {
        [$maxAttempts, $decayMinutes] = $this->parseLimit($limit);

        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return ApiResponse::error(
                message: 'Too many requests. Please try again later.',
                statusCode: Response::HTTP_TOO_MANY_REQUESTS,
                errorCode: 'RATE_LIMIT_EXCEEDED',
                meta: [
                    'retry_after' => $retryAfter,
                    'retry_after_human' => gmdate('H:i:s', $retryAfter),
                ]
            )->header('Retry-After', (string) $retryAfter)
                ->header('X-RateLimit-Limit', (string) $maxAttempts)
                ->header('X-RateLimit-Remaining', '0');
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        $remaining = RateLimiter::remaining($key, $maxAttempts);

        return $response
            ->header('X-RateLimit-Limit', (string) $maxAttempts)
            ->header('X-RateLimit-Remaining', (string) $remaining);
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();

        if ($user) {
            return 'rate-limit:user:'.$user->id;
        }

        return 'rate-limit:ip:'.$request->ip();
    }

    /**
     * Parse the rate limit parameter
     */
    protected function parseLimit(string $limit): array
    {
        $parts = explode(',', $limit);

        return [
            (int) ($parts[0] ?? 60),     // max attempts
            (int) ($parts[1] ?? 1),      // decay minutes
        ];
    }
}
