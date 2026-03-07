<?php

namespace App\Http\Middleware;

use App\Services\KeycloakService;
use App\Repositories\Interfaces\TenantRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        private readonly KeycloakService           $keycloak,
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $claims = $request->attributes->get('jwt_claims');

        // Fallback: try the X-Tenant-ID header (service-to-service calls)
        $tenantId = $claims
            ? $this->keycloak->extractTenantId($claims)
            : $request->header(config('tenant.header', 'X-Tenant-ID'));

        if (! $tenantId) {
            return response()->json([
                'error'   => 'Tenant resolution failed',
                'message' => 'Could not determine tenant from token or request.',
            ], Response::HTTP_FORBIDDEN);
        }

        $tenant = $this->tenantRepository->findById((int) $tenantId);

        if (! $tenant) {
            return response()->json([
                'error'   => 'Tenant not found',
                'message' => "Tenant {$tenantId} does not exist.",
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $tenant->isActive()) {
            return response()->json([
                'error'   => 'Tenant inactive',
                'message' => 'This tenant account is suspended or inactive.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Make tenant context available throughout the request lifecycle
        $request->attributes->set('tenant',    $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        return $next($request);
    }
}
