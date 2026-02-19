<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware
 *
 * Resolves and sets the tenant context for each request
 */
class TenantMiddleware
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to resolve tenant from various sources
        $tenant = $this->resolveTenant($request);

        if ($tenant) {
            $this->tenantContext->setTenant($tenant);
        }

        return $next($request);
    }

    /**
     * Resolve tenant from request
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        // 1. Try from header
        if ($tenantId = $request->header('X-Tenant-ID')) {
            return Tenant::find($tenantId);
        }

        // 2. Try from subdomain
        $host = $request->getHost();
        if ($tenant = $this->resolveFromDomain($host)) {
            return $tenant;
        }

        // 3. Try from route parameter
        if ($tenantId = $request->route('tenant_id')) {
            return Tenant::find($tenantId);
        }

        // 4. Try from authenticated user (if available)
        if ($request->user() && method_exists($request->user(), 'tenant')) {
            return $request->user()->tenant;
        }

        return null;
    }

    /**
     * Resolve tenant from domain/subdomain
     */
    protected function resolveFromDomain(string $host): ?Tenant
    {
        // Direct domain match
        $tenant = Tenant::where('domain', $host)->active()->first();
        if ($tenant) {
            return $tenant;
        }

        // Subdomain match (e.g., tenant1.example.com)
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $subdomain = $parts[0];

            return Tenant::where('slug', $subdomain)->active()->first();
        }

        return null;
    }
}
