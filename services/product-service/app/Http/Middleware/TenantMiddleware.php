<?php

namespace App\Http\Middleware;

use App\Services\KeycloakService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TenantMiddleware
{
    public function __construct(private readonly KeycloakService $keycloakService) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $claims = $request->attributes->get('jwt_claims');

        if (! $claims) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Authentication required before tenant resolution',
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $tenantId = $this->keycloakService->extractTenantId($claims);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tenant identification failed: '.$e->getMessage(),
            ], Response::HTTP_FORBIDDEN);
        }

        // Bind tenant_id into the IoC container so global scopes can access it
        app()->instance('tenant_id', $tenantId);

        // Make it available on the request object
        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }
}
