<?php

namespace App\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Identification Middleware
 *
 * Identifies the tenant from the request and sets it in the application context
 * Supports identification via:
 * - Subdomain (tenant.domain.com)
 * - Custom domain (tenant-domain.com)
 * - Header (X-Tenant-ID)
 */
class TenantIdentification
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For API requests with authentication, tenant is identified from user
        if ($request->user()) {
            return $next($request);
        }

        // Try to identify tenant from subdomain
        $host = $request->getHost();
        $subdomain = $this->getSubdomain($host);

        if ($subdomain) {
            // Store tenant identifier for later use
            $request->attributes->set('tenant_subdomain', $subdomain);
        }

        // Try to identify tenant from header (useful for API clients)
        if ($request->hasHeader('X-Tenant-ID')) {
            $request->attributes->set('tenant_id', $request->header('X-Tenant-ID'));
        }

        return $next($request);
    }

    /**
     * Extract subdomain from host
     */
    protected function getSubdomain(string $host): ?string
    {
        $parts = explode('.', $host);

        // If we have at least 3 parts (subdomain.domain.tld), extract subdomain
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }
}
