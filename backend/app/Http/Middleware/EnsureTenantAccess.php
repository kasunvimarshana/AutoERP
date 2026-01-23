<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Super admin has access to all tenants
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Check if user has a tenant
        if (!$user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tenant assigned to user',
            ], 403);
        }

        // Check if tenant is active and has valid subscription
        $tenant = $user->tenant;

        if (!$tenant || !$tenant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant is inactive',
            ], 403);
        }

        if (!$tenant->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant subscription has expired',
            ], 403);
        }

        // Add tenant context to request
        $request->merge(['tenant_id' => $user->tenant_id]);

        return $next($request);
    }
}
