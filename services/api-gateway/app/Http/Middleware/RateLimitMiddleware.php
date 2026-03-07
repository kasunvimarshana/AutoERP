<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Requests-per-minute ceiling per role.
     * Values here act as the ceiling; unauthenticated callers get the lowest limit.
     */
    private const LIMITS = [
        'admin'   => 1000,
        'manager' => 500,
        'user'    => 100,
        'guest'   => 30,
    ];

    public function __construct(
        private readonly RateLimiter $limiter
    ) {}

    /**
     * Enforce per-tenant, per-role rate limiting using Redis via Laravel's
     * built-in RateLimiter (backed by the cache driver).
     */
    public function handle(Request $request, Closure $next): Response
    {
        [$key, $maxAttempts, $decaySeconds] = $this->buildLimit($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = $this->limiter->availableIn($key);

            return response()->json([
                'message'     => 'Too many requests. Please slow down.',
                'retry_after' => $retryAfter,
            ], 429, [
                'Retry-After'           => $retryAfter,
                'X-RateLimit-Limit'     => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->limiter->hit($key, $decaySeconds);

        $remaining = max(0, $maxAttempts - $this->limiter->attempts($key));

        $response = $next($request);

        // Attach informational headers so clients can self-throttle.
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);

        return $response;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Derive the cache key, ceiling, and decay window for this request.
     *
     * Keys are namespaced by tenant (when available) so that a noisy tenant
     * cannot consume another tenant's quota.
     *
     * @return array{string, int, int}  [key, maxAttempts, decaySeconds]
     */
    private function buildLimit(Request $request): array
    {
        $user     = $request->user();
        $tenantId = $request->attributes->get('tenant_id', 'none');
        $role     = $user?->role ?? 'guest';
        $limit    = self::LIMITS[$role] ?? self::LIMITS['guest'];

        if ($user !== null) {
            // Authenticated: bucket by tenant + user.
            $key = "rl:tenant:{$tenantId}:user:{$user->id}";
        } else {
            // Unauthenticated: bucket by tenant + client IP.
            $key = "rl:tenant:{$tenantId}:ip:" . $request->ip();
        }

        // Decay window = 60 seconds (1 minute).
        return [$key, $limit, 60];
    }
}
