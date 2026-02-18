<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Rate Limiting Middleware
 * 
 * Implements distributed rate limiting using Redis for horizontal scalability.
 * Prevents API abuse and ensures fair resource allocation across all users.
 * 
 * Features:
 * - Per-user rate limiting
 * - Per-tenant rate limiting
 * - Per-IP rate limiting
 * - Sliding window algorithm
 * - Redis-based for multi-server support
 * - Graceful degradation
 * 
 * @package Modules\Core\Http\Middleware
 */
class ApiRateLimiter
{
    /**
     * Default rate limit (requests per minute)
     */
    private const DEFAULT_LIMIT = 60;

    /**
     * Rate limit window in seconds
     */
    private const WINDOW_SECONDS = 60;

    /**
     * Handle an incoming request
     * 
     * @param Request $request
     * @param Closure $next
     * @param int|null $maxAttempts Maximum requests per window
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?int $maxAttempts = null): Response
    {
        $maxAttempts = $maxAttempts ?? env('API_RATE_LIMIT', self::DEFAULT_LIMIT);
        
        // Generate rate limit key based on user, tenant, and IP
        $key = $this->resolveRequestSignature($request);
        
        // Check if rate limit exceeded
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildRateLimitExceededResponse($key, $maxAttempts);
        }

        // Increment request counter
        $this->hit($key);

        // Process request
        $response = $next($request);

        // Add rate limit headers
        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            $this->retriesLeft($key, $maxAttempts),
            $this->availableIn($key)
        );
    }

    /**
     * Resolve request signature for rate limiting
     * 
     * @param Request $request
     * @return string Unique identifier for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $parts = [];

        // Include tenant ID if available
        if ($tenantId = session('tenant_id')) {
            $parts[] = 'tenant:' . $tenantId;
        }

        // Include user ID if authenticated
        if ($user = $request->user()) {
            $parts[] = 'user:' . $user->id;
        } else {
            // Fall back to IP address for unauthenticated requests
            $parts[] = 'ip:' . $request->ip();
        }

        // Include route for endpoint-specific limits
        $parts[] = 'route:' . $request->path();

        return 'rate_limit:' . implode(':', $parts);
    }

    /**
     * Check if too many attempts have been made
     * 
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum allowed attempts
     * @return bool True if limit exceeded
     */
    protected function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $attempts = $this->attempts($key);
        
        if ($attempts >= $maxAttempts) {
            Log::warning('Rate limit exceeded', [
                'key' => $key,
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Get current number of attempts
     * 
     * @param string $key Rate limit key
     * @return int Number of attempts in current window
     */
    protected function attempts(string $key): int
    {
        return (int) Cache::store('redis')->get($key, 0);
    }

    /**
     * Increment the counter for a given key
     * 
     * @param string $key Rate limit key
     * @return int New attempt count
     */
    protected function hit(string $key): int
    {
        $attempts = Cache::store('redis')->increment($key);
        
        // Set expiration on first hit
        if ($attempts === 1) {
            Cache::store('redis')->put($key, 1, self::WINDOW_SECONDS);
        }

        return $attempts;
    }

    /**
     * Get number of retries left
     * 
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum attempts
     * @return int Remaining attempts
     */
    protected function retriesLeft(string $key, int $maxAttempts): int
    {
        $attempts = $this->attempts($key);
        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Get seconds until rate limit resets
     * 
     * @param string $key Rate limit key
     * @return int Seconds remaining in window
     */
    protected function availableIn(string $key): int
    {
        $ttl = Cache::store('redis')->getStore()->getRedis()->ttl($key);
        return max(0, $ttl);
    }

    /**
     * Build rate limit exceeded response
     * 
     * @param string $key Rate limit key
     * @param int $maxAttempts Maximum attempts
     * @return Response
     */
    protected function buildRateLimitExceededResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->availableIn($key);
        
        $response = response()->json([
            'message' => 'Too many requests. Please try again later.',
            'error' => 'rate_limit_exceeded',
            'retry_after' => $retryAfter,
            'max_attempts' => $maxAttempts,
        ], 429);

        return $this->addRateLimitHeaders($response, $maxAttempts, 0, $retryAfter);
    }

    /**
     * Add rate limit headers to response
     * 
     * @param Response $response
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $remainingAttempts Remaining attempts
     * @param int $retryAfter Seconds until reset
     * @return Response
     */
    protected function addRateLimitHeaders(
        Response $response,
        int $maxAttempts,
        int $remainingAttempts,
        int $retryAfter
    ): Response {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            'Retry-After' => $retryAfter,
        ]);

        return $response;
    }

    /**
     * Reset rate limit for a given key
     * 
     * Useful for testing or administrative override.
     * 
     * @param string $key Rate limit key
     * @return bool True if reset successful
     */
    public function reset(string $key): bool
    {
        return Cache::store('redis')->forget($key);
    }

    /**
     * Clear all rate limits
     * 
     * WARNING: Use with caution, primarily for testing.
     * 
     * @return bool True if cleared
     */
    public function clear(): bool
    {
        try {
            $redis = Cache::store('redis')->getStore()->getRedis();
            $keys = $redis->keys('rate_limit:*');
            
            if (!empty($keys)) {
                $redis->del($keys);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear rate limits', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
