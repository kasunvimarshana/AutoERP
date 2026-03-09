<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Repositories\TenantRepositoryInterface;
use App\Contracts\Services\TenantServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Middleware
 *
 * Resolves the current tenant from the request using:
 * 1. X-Tenant-ID header
 * 2. Subdomain (tenant.saas.com)
 * 3. Custom domain
 *
 * Applies tenant-specific runtime configuration.
 */
class TenantMiddleware
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly TenantServiceInterface $tenantService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or inactive.',
                'error_code' => 'TENANT_NOT_FOUND',
            ], 404);
        }

        if (!$tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant account is not active.',
                'error_code' => 'TENANT_INACTIVE',
            ], 403);
        }

        // Set tenant context
        $this->tenantService->setContext($tenant);

        // Apply tenant-specific runtime configuration
        $this->tenantService->applyRuntimeConfig($tenant);

        // Inject tenant ID into request for downstream use
        $request->headers->set('X-Tenant-ID', $tenant->id);
        $request->merge(['_tenant_id' => $tenant->id]);

        return $next($request);
    }

    /**
     * Resolve tenant from the request.
     */
    private function resolveTenant(Request $request): ?\App\Domain\Tenant\Models\Tenant
    {
        // 1. Check X-Tenant-ID header
        if ($tenantId = $request->header('X-Tenant-ID')) {
            return $this->tenantRepository->findById($tenantId);
        }

        // 2. Check X-Tenant-Slug header
        if ($slug = $request->header('X-Tenant-Slug')) {
            return $this->tenantRepository->findBySlug($slug);
        }

        // 3. Resolve from subdomain
        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) >= 3) {
            $subdomain = $parts[0];
            return $this->tenantRepository->findBySlug($subdomain);
        }

        // 4. Resolve from full domain
        return $this->tenantRepository->findByDomain($host);
    }
}
