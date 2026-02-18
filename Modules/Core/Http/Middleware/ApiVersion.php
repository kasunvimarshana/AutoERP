<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Version Middleware
 *
 * Handles API versioning in request URLs or headers.
 * Supports multiple versioning strategies as analyzed in OpenAPI/Swagger patterns.
 *
 * Versioning strategies supported:
 * 1. URL-based: /api/v1/resource
 * 2. Header-based: Accept: application/vnd.api.v1+json
 *
 * Usage: Apply to API routes
 * Route::middleware(['api.version'])->group(...)
 */
class ApiVersion
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $version = null): Response
    {
        // Extract version from URL or header
        $apiVersion = $version ?? $this->extractVersion($request);

        // Set version in request for controllers/services to use
        $request->merge(['api_version' => $apiVersion]);

        // Add version to response headers
        $response = $next($request);

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->header('X-API-Version', $apiVersion);
        }

        return $response;
    }

    /**
     * Extract API version from request.
     */
    private function extractVersion(Request $request): string
    {
        // Try to extract from URL path
        if (preg_match('/\/v(\d+)\//', $request->path(), $matches)) {
            return 'v'.$matches[1];
        }

        // Try to extract from Accept header
        $accept = $request->header('Accept', '');
        if (preg_match('/vnd\.api\.v(\d+)/', $accept, $matches)) {
            return 'v'.$matches[1];
        }

        // Default version
        return config('app.api_version', 'v1');
    }
}
