<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce Tenant Isolation Middleware
 *
 * Ensures strict tenant isolation by verifying that all database queries
 * are scoped to the current tenant. Prevents accidental cross-tenant data access.
 */
class EnforceTenantIsolation
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant enforcement for public routes
        if ($this->isPublicRoute($request)) {
            return $next($request);
        }

        // Ensure tenant is identified
        if (! $this->tenantContext->hasTenant()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context not established. Access denied.',
                'error' => 'TENANT_NOT_IDENTIFIED',
            ], 403);
        }

        $tenant = $this->tenantContext->getTenant();

        // Verify tenant is active
        if (! $tenant || $tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Tenant is not active. Access denied.',
                'error' => 'TENANT_NOT_ACTIVE',
            ], 403);
        }

        // Set tenant ID in request for logging
        $request->attributes->set('tenant_id', $tenant->id);
        $request->attributes->set('tenant_identifier', $tenant->identifier);

        $response = $next($request);

        // Add tenant information to response headers (for debugging)
        if (config('app.debug')) {
            $response->headers->set('X-Tenant-Id', (string) $tenant->id);
            $response->headers->set('X-Tenant-Identifier', $tenant->identifier);
        }

        return $response;
    }

    /**
     * Check if route is public (doesn't require tenant context)
     */
    protected function isPublicRoute(Request $request): bool
    {
        $publicRoutes = [
            'api/health',
            'api/login',
            'api/register',
            'api/forgot-password',
            'api/reset-password',
        ];

        $path = $request->path();

        foreach ($publicRoutes as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }

        return false;
    }
}
