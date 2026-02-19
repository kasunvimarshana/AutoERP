<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Modules\Core\Exceptions\RateLimitExceededException;

class ApiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $limit
     * @return mixed
     *
     * @throws \Modules\Core\Exceptions\RateLimitExceededException
     */
    public function handle(Request $request, Closure $next, string $limit = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request, $limit);
        $config = $this->getConfig($limit);

        // Check rate limit
        $executed = RateLimiter::attempt(
            $key,
            $config['max_attempts'],
            function () use ($next, $request) {
                return $next($request);
            },
            $config['decay_seconds']
        );

        if (!$executed) {
            $retryAfter = RateLimiter::availableIn($key);
            
            throw new RateLimitExceededException(
                "Rate limit exceeded. Try again in {$retryAfter} seconds.",
                [],
                $retryAfter
            );
        }

        // Add rate limit headers to response
        $response = $executed;
        
        if ($response instanceof Response) {
            $this->addRateLimitHeaders($response, $key, $config);
        }

        return $response;
    }

    /**
     * Resolve the request signature for rate limiting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $limit
     * @return string
     */
    protected function resolveRequestSignature(Request $request, string $limit): string
    {
        $user = $request->user();
        $tenantId = $request->header('X-Tenant-ID') ?? 'guest';
        
        if ($user) {
            // Authenticated user: limit per user per tenant
            return "api_rate_limit:{$limit}:user:{$user->id}:tenant:{$tenantId}";
        }

        // Guest: limit per IP per tenant
        $ip = $request->ip();
        return "api_rate_limit:{$limit}:ip:{$ip}:tenant:{$tenantId}";
    }

    /**
     * Get rate limit configuration.
     *
     * @param  string  $limit
     * @return array
     */
    protected function getConfig(string $limit): array
    {
        $limits = config('api.rate_limits', []);

        if (isset($limits[$limit])) {
            return $limits[$limit];
        }

        // Default configuration
        return [
            'max_attempts' => 60,
            'decay_seconds' => 60,
        ];
    }

    /**
     * Add rate limit headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $key
     * @param  array  $config
     * @return void
     */
    protected function addRateLimitHeaders(Response $response, string $key, array $config): void
    {
        $remaining = RateLimiter::remaining($key, $config['max_attempts']);
        $retryAfter = RateLimiter::availableIn($key);

        $response->headers->add([
            'X-RateLimit-Limit' => $config['max_attempts'],
            'X-RateLimit-Remaining' => max(0, $remaining),
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);

        if ($remaining === 0) {
            $response->headers->set('Retry-After', $retryAfter);
        }
    }
}
