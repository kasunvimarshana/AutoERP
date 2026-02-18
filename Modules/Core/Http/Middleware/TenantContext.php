<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Context Middleware
 *
 * Validates and initializes tenant context for API requests using native Laravel features.
 * Implements multi-tenant isolation at the middleware layer following
 * the Emmy Awards architecture patterns.
 *
 * This middleware:
 * - Identifies tenant from subdomain, header, or parameter
 * - Stores tenant context in session
 * - Returns 403 if tenant not found or inactive
 *
 * Usage: Apply to tenant-specific routes
 * Route::middleware(['tenant.context'])->group(...)
 */
class TenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if (! $tenantId) {
            return response()->json([
                'message' => 'Tenant identification required',
            ], Response::HTTP_FORBIDDEN);
        }

        // Validate tenant exists and is active
        $tenant = $this->getTenant($tenantId);

        if (! $tenant || ! $tenant->is_active) {
            return response()->json([
                'message' => 'Invalid or inactive tenant',
            ], Response::HTTP_FORBIDDEN);
        }

        // Store tenant in session for the request
        Session::put('tenant_id', $tenant->id);
        $request->merge(['tenant_id' => $tenant->id]);

        return $next($request);
    }

    /**
     * Resolve tenant ID from request.
     */
    protected function resolveTenantId(Request $request): ?string
    {
        // Try from header first (API requests)
        if ($request->header('X-Tenant-ID')) {
            return $request->header('X-Tenant-ID');
        }

        // Try from subdomain
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $subdomain = $parts[0];
            if ($subdomain !== 'www' && $subdomain !== 'api') {
                return $this->getTenantIdBySubdomain($subdomain);
            }
        }

        // Try from query parameter (fallback)
        if ($request->query('tenant_id')) {
            return $request->query('tenant_id');
        }

        // Try from authenticated user
        if (auth()->check() && method_exists(auth()->user(), 'getCurrentTenantId')) {
            return auth()->user()->getCurrentTenantId();
        }

        return null;
    }

    /**
     * Get tenant by ID.
     */
    protected function getTenant(string $tenantId): ?object
    {
        $tenantModel = config('tenancy.tenant_model', 'App\Models\Tenant');

        return $tenantModel::find($tenantId);
    }

    /**
     * Get tenant ID by subdomain.
     */
    protected function getTenantIdBySubdomain(string $subdomain): ?string
    {
        $tenantModel = config('tenancy.tenant_model', 'App\Models\Tenant');

        $tenant = $tenantModel::where('subdomain', $subdomain)->first();

        return $tenant?->id;
    }
}
