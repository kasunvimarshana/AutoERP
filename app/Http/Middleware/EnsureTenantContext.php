<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce tenant context isolation on every authenticated request.
 *
 * Ensures the user's tenant matches the tenant_id provided in the request
 * (header or query string), preventing cross-tenant data access.
 */
final class EnsureTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Allow super-admins (no tenant) to bypass
        if ($user->tenant_id === null) {
            return $next($request);
        }

        // Resolve the requested tenant from header or query param
        $requestedTenantId = $request->header('X-Tenant-ID')
            ?? $request->query('tenant_id')
            ?? null;

        if ($requestedTenantId !== null && $requestedTenantId !== $user->tenant_id) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'TENANT_MISMATCH',
                    'message' => 'Cross-tenant access is not permitted.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        // Bind the authenticated tenant into the request for downstream use
        $request->merge(['_resolved_tenant_id' => $user->tenant_id]);

        return $next($request);
    }
}
