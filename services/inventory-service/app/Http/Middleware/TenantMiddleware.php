<?php

namespace App\Http\Middleware;

use App\Services\KeycloakService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(
        private readonly KeycloakService $keycloak,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $claims = $request->attributes->get('jwt_claims');

        // Fallback to X-Tenant-ID header for service-to-service calls
        $tenantId = $claims
            ? $this->keycloak->extractTenantId($claims)
            : $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return response()->json([
                'error'   => 'Tenant resolution failed',
                'message' => 'Could not determine tenant from token or request.',
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('tenant_id', (int) $tenantId);

        return $next($request);
    }
}
