<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Version Middleware
 * 
 * Handles API versioning through multiple strategies:
 * 1. URL Path: /api/v1/products
 * 2. Header: Accept: application/vnd.autoerp.v1+json
 * 3. Query Parameter: ?api_version=v1
 * 
 * Enforces API version compatibility and deprecation warnings.
 */
class ApiVersion
{
    /**
     * Supported API versions
     */
    private const SUPPORTED_VERSIONS = ['v1', 'v2'];

    /**
     * Default API version
     */
    private const DEFAULT_VERSION = 'v1';

    /**
     * Deprecated API versions with sunset dates
     */
    private const DEPRECATED_VERSIONS = [
        // 'v1' => '2027-01-01', // Example: v1 deprecated, sunset date
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $requiredVersion = null): Response
    {
        // Resolve API version from request
        $version = $this->resolveApiVersion($request);
        
        // Validate version
        if (!in_array($version, self::SUPPORTED_VERSIONS)) {
            return response()->json([
                'error' => 'Unsupported API version',
                'message' => "API version '{$version}' is not supported.",
                'supported_versions' => self::SUPPORTED_VERSIONS,
            ], 400);
        }

        // Check if specific version is required by route
        if ($requiredVersion && $version !== $requiredVersion) {
            return response()->json([
                'error' => 'API version mismatch',
                'message' => "This endpoint requires API version '{$requiredVersion}'.",
                'current_version' => $version,
            ], 400);
        }

        // Store version in request for controllers
        $request->attributes->set('api_version', $version);

        // Execute request
        $response = $next($request);

        // Add version headers to response
        $response->headers->set('X-API-Version', $version);
        $response->headers->set('X-Supported-Versions', implode(', ', self::SUPPORTED_VERSIONS));

        // Add deprecation warning if applicable
        if (isset(self::DEPRECATED_VERSIONS[$version])) {
            $sunsetDate = self::DEPRECATED_VERSIONS[$version];
            $response->headers->set('X-API-Deprecation', 'true');
            $response->headers->set('X-API-Sunset', $sunsetDate);
            $response->headers->set('Sunset', $sunsetDate); // Standard header
        }

        return $response;
    }

    /**
     * Resolve API version from request
     * 
     * Priority:
     * 1. URL path segment (/api/v1/...)
     * 2. Accept header (application/vnd.autoerp.v1+json)
     * 3. Query parameter (?api_version=v1)
     * 4. Default version
     */
    private function resolveApiVersion(Request $request): string
    {
        // 1. Check URL path
        if (preg_match('/\/(v\d+)\//', $request->path(), $matches)) {
            return $matches[1];
        }

        // 2. Check Accept header
        $acceptHeader = $request->header('Accept', '');
        if (preg_match('/application\/vnd\.autoerp\.(v\d+)\+json/', $acceptHeader, $matches)) {
            return $matches[1];
        }

        // 3. Check query parameter
        $queryVersion = $request->query('api_version');
        if ($queryVersion && in_array($queryVersion, self::SUPPORTED_VERSIONS)) {
            return $queryVersion;
        }

        // 4. Use default
        return self::DEFAULT_VERSION;
    }

    /**
     * Get supported API versions
     */
    public static function getSupportedVersions(): array
    {
        return self::SUPPORTED_VERSIONS;
    }

    /**
     * Get default API version
     */
    public static function getDefaultVersion(): string
    {
        return self::DEFAULT_VERSION;
    }

    /**
     * Check if version is deprecated
     */
    public static function isDeprecated(string $version): bool
    {
        return isset(self::DEPRECATED_VERSIONS[$version]);
    }

    /**
     * Get sunset date for deprecated version
     */
    public static function getSunsetDate(string $version): ?string
    {
        return self::DEPRECATED_VERSIONS[$version] ?? null;
    }
}
