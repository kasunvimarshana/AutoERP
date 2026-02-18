<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Middleware
 *
 * Resolves and sets the current tenant based on the request.
 * Ensures all subsequent operations are scoped to the tenant.
 */
class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get tenant from subdomain or header
        $tenant = $this->resolveTenant($request);

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'Unable to identify tenant from request',
            ], 404);
        }

        // Check if tenant is active
        if (! $tenant->isActive()) {
            return response()->json([
                'error' => 'Tenant inactive',
                'message' => 'This tenant account is currently inactive',
            ], 403);
        }

        // Set tenant in global context
        app()->instance('tenant', $tenant);

        return $next($request);
    }

    /**
     * Resolve tenant from request.
     *
     * @return \Modules\Tenancy\Entities\Tenant|null
     */
    protected function resolveTenant(Request $request)
    {
        // Try to get from header first (for API requests)
        if ($request->hasHeader('X-Tenant-ID')) {
            return \Modules\Tenancy\Entities\Tenant::find($request->header('X-Tenant-ID'));
        }

        if ($request->hasHeader('X-Tenant-Slug')) {
            return \Modules\Tenancy\Entities\Tenant::where('slug', $request->header('X-Tenant-Slug'))->first();
        }

        // Try to get from subdomain
        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) > 2) {
            $subdomain = $parts[0];

            return \Modules\Tenancy\Entities\Tenant::where('slug', $subdomain)->first();
        }

        // Try to get from domain
        return \Modules\Tenancy\Entities\Tenant::where('domain', $host)->first();
    }
}
