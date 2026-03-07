<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if (! $tenantId) {
            return $this->tenantMissingResponse();
        }

        // In order-service, tenant existence is validated via the JWT claim or header.
        // We bind the tenant_id so repositories can scope queries automatically.
        App::instance('current_tenant_id', $tenantId);
        $request->attributes->set('tenant_id', $tenantId);

        Log::debug('TenantMiddleware: tenant resolved', ['tenant_id' => $tenantId]);

        return $next($request);
    }

    private function resolveTenantId(Request $request): ?string
    {
        // 1. Explicit header (preferred for inter-service calls)
        $headerName = config('tenant.header', 'X-Tenant-ID');
        if ($id = $request->header($headerName)) {
            return (string) $id;
        }

        // 2. JWT claim (when request carries a Passport/JWT token)
        if ($user = $request->user()) {
            return isset($user->tenant_id) ? (string) $user->tenant_id : null;
        }

        return null;
    }

    private function tenantMissingResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Tenant identification required. Provide the X-Tenant-ID header.',
        ], 400);
    }
}
