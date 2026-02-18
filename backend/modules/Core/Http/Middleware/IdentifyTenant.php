<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\TenantContext;
use Modules\Core\Services\TenantService;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    protected TenantContext $tenantContext;

    protected TenantService $tenantService;

    public function __construct(TenantContext $tenantContext, TenantService $tenantService)
    {
        $this->tenantContext = $tenantContext;
        $this->tenantService = $tenantService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->identifyTenant($request);

        if ($tenant) {
            if (! $tenant->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant is not active',
                ], 403);
            }

            $this->tenantContext->setTenant($tenant);
        }

        return $next($request);
    }

    protected function identifyTenant(Request $request)
    {
        // Try to identify tenant from header
        if ($tenantUuid = $request->header('X-Tenant-ID')) {
            return $this->tenantService->getTenantByUuid($tenantUuid);
        }

        // Try to identify tenant from subdomain
        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) > 2) {
            $subdomain = $parts[0];

            return $this->tenantService->getTenantByDomain($subdomain);
        }

        // Try to identify tenant from custom domain
        return $this->tenantService->getTenantByDomain($host);
    }
}
