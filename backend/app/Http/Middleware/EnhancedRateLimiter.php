<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

/**
 * Enhanced Rate Limiter Middleware
 * 
 * Implements tiered rate limiting based on:
 * - User authentication status
 * - User role/subscription level
 * - API endpoint sensitivity
 * - Tenant quotas
 */
class EnhancedRateLimiter
{
    /**
     * Rate limit tiers (requests per minute)
     */
    private const TIERS = [
        'anonymous' => 10,      // Unauthenticated requests
        'authenticated' => 60,  // Authenticated users
        'premium' => 120,       // Premium tier users
        'admin' => 300,         // Admin users
        'system' => 0,          // No limit for system operations
    ];

    /**
     * Endpoint-specific rate limits (overrides tier limits)
     */
    private const ENDPOINT_LIMITS = [
        'auth.login' => 5,              // Login attempts
        'auth.register' => 3,           // Registration attempts
        'auth.forgot-password' => 3,    // Password reset requests
        'inventory.bulk-import' => 10,  // Bulk import operations
    ];

    /**
     * The rate limiter instance
     */
    protected RateLimiter $limiter;

    /**
     * Create a new middleware instance
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next, ?string $tier = null): Response
    {
        // Determine rate limit key and max attempts
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = $this->resolveMaxAttempts($request, $tier);
        $decayMinutes = 1; // Rate limit window

        // Check if rate limit is disabled for this tier
        if ($maxAttempts === 0) {
            return $next($request);
        }

        // Check rate limit
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildRateLimitResponse($key, $maxAttempts);
        }

        // Increment attempt counter
        $this->limiter->hit($key, $decayMinutes * 60);

        // Execute request
        $response = $next($request);

        // Add rate limit headers
        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            $this->limiter->retriesLeft($key, $maxAttempts),
            $this->limiter->availableIn($key)
        );
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $userId = $request->user()?->id ?? 'guest';
        $tenantId = $request->attributes->get('tenant_id', 'no-tenant');
        $endpoint = $request->route()?->getName() ?? 'unknown';
        $ip = $request->ip();

        return "rate-limit:{$tenantId}:{$userId}:{$endpoint}:{$ip}";
    }

    /**
     * Resolve maximum attempts based on user tier and endpoint
     */
    protected function resolveMaxAttempts(Request $request, ?string $tier = null): int
    {
        // Check endpoint-specific limits first
        $routeName = $request->route()?->getName();
        if ($routeName && isset(self::ENDPOINT_LIMITS[$routeName])) {
            return self::ENDPOINT_LIMITS[$routeName];
        }

        // Use provided tier or determine from user
        if (!$tier) {
            $tier = $this->determineTier($request);
        }

        return self::TIERS[$tier] ?? self::TIERS['authenticated'];
    }

    /**
     * Determine user tier based on authentication and roles
     */
    protected function determineTier(Request $request): string
    {
        $user = $request->user();

        if (!$user) {
            return 'anonymous';
        }

        // Check if user has admin role
        // Note: Requires Spatie Permission package with hasRole method
        // If not available, this will gracefully fall back to 'authenticated'
        if (method_exists($user, 'hasRole')) {
            try {
                if ($user->hasRole(['super-admin', 'admin'])) {
                    return 'admin';
                }
            } catch (\Exception $e) {
                // If role check fails, continue to next tier check
            }
        }

        // Check for premium subscription
        // This assumes a 'subscription_tier' attribute on the user model
        if ($user->getAttribute('subscription_tier') === 'premium') {
            return 'premium';
        }

        // Default to authenticated tier
        return 'authenticated';
    }

    /**
     * Build rate limit exceeded response
     */
    protected function buildRateLimitResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $retryAfter,
            'retry_after_seconds' => $retryAfter,
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'Retry-After' => $retryAfter,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders(
        Response $response,
        int $maxAttempts,
        int $remainingAttempts,
        int $retryAfter
    ): Response {
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remainingAttempts));
        
        if ($remainingAttempts <= 0) {
            $response->headers->set('X-RateLimit-Reset', (string) now()->addSeconds($retryAfter)->timestamp);
        }

        return $response;
    }

    /**
     * Get rate limit tiers
     */
    public static function getTiers(): array
    {
        return self::TIERS;
    }

    /**
     * Get endpoint-specific limits
     */
    public static function getEndpointLimits(): array
    {
        return self::ENDPOINT_LIMITS;
    }
}
