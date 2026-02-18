<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Modules\Core\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Rate Limiter Middleware
 * 
 * Implements tenant-aware rate limiting to prevent API abuse
 * and ensure fair resource allocation across tenants.
 */
class ApiRateLimiter
{
    public function __construct(
        protected RateLimiter $limiter,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            $this->limiter->retriesLeft($key, $maxAttempts)
        );
    }

    /**
     * Resolve request signature for rate limiting
     * Combines tenant, user, and IP for granular control
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $parts = [];

        // Add tenant identifier if available
        if ($this->tenantContext->hasTenant()) {
            $tenant = $this->tenantContext->getTenant();
            $parts[] = "tenant:{$tenant->id}";
        }

        // Add user identifier if authenticated
        if ($request->user()) {
            $parts[] = "user:{$request->user()->id}";
        } else {
            // Fallback to IP address for unauthenticated requests
            $parts[] = "ip:{$request->ip()}";
        }

        // Add route for endpoint-specific limits
        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        $parts[] = "route:" . ($routeName ?? $request->path());

        return implode(':', $parts);
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'error' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $remainingAttempts);

        return $response;
    }
}
