<?php

declare(strict_types=1);

namespace App\Infrastructure\MultiTenant;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * TenantResolver — extracts the tenant identifier from an incoming request.
 *
 * Resolution strategy (first match wins):
 *   1. `X-Tenant-ID` header
 *   2. `X-Tenant-Slug` header
 *   3. Subdomain (e.g. acme.inventory.example.com → acme)
 *   4. Custom domain lookup
 */
class TenantResolver
{
    public function __construct(
        private readonly TenantManager $tenantManager
    ) {}

    /**
     * Resolve the tenant from the request and set it on the TenantManager.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException When no tenant can be resolved.
     */
    public function resolve(Request $request): Tenant
    {
        $tenant = $this->tryResolve($request);

        if ($tenant === null) {
            abort(404, 'Tenant not found or inactive.');
        }

        if (!$tenant->isActive()) {
            abort(403, "Tenant '{$tenant->slug}' is not active.");
        }

        $this->tenantManager->setCurrentTenant($tenant);

        return $tenant;
    }

    /**
     * Attempt resolution without throwing; returns null on failure.
     */
    public function tryResolve(Request $request): ?Tenant
    {
        // 1. Explicit header by id.
        if ($tenantId = $request->header('X-Tenant-ID')) {
            $tenant = $this->tenantManager->resolveTenantById((int) $tenantId);
            if ($tenant) return $tenant;
        }

        // 2. Explicit header by slug.
        if ($slug = $request->header('X-Tenant-Slug')) {
            $tenant = $this->tenantManager->resolveTenantBySlug($slug);
            if ($tenant) return $tenant;
        }

        // 3. Subdomain extraction.
        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            $tenant = $this->tenantManager->resolveTenantBySlug($subdomain);
            if ($tenant) return $tenant;
        }

        // 4. Full domain lookup.
        $tenant = $this->tenantManager->resolveTenantByDomain($host);
        if ($tenant) return $tenant;

        Log::warning("[TenantResolver] Could not resolve tenant for host: {$host}");

        return null;
    }
}
