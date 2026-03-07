<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        // Bind tenant ID to the request for downstream use
        $request->attributes->set('tenant_id', $tenantId);

        Log::debug('Tenant resolved for inventory service', ['tenant_id' => $tenantId]);

        return $next($request);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveTenantId(Request $request): ?string
    {
        // 1. Header (preferred for API clients)
        $headerName = config('tenant.header', 'X-Tenant-ID');
        if ($id = $request->header($headerName)) {
            return (string) $id;
        }

        // 2. Authenticated user's tenant_id claim (from Passport token)
        if ($user = $request->user()) {
            $tenantId = $user->tenant_id ?? null;
            if ($tenantId) {
                return (string) $tenantId;
            }
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
