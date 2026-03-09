<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Middleware
 *
 * Ensures tenant context is present on all requests.
 * Tenant is resolved either from auth user data or X-Tenant-ID header.
 */
class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Tenant may already be set by AuthServiceMiddleware
        $tenantId = $request->attributes->get('tenant_id')
            ?? $request->header('X-Tenant-ID');

        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context required.',
            ], 400);
        }

        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }
}
