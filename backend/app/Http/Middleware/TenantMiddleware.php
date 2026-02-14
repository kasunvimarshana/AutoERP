<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware
 * 
 * Ensures that authenticated users have a valid tenant context
 * and sets the tenant ID for the current request.
 */
class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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

        if (!$user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'User does not belong to any tenant',
            ], 403);
        }

        // Set tenant ID in request attributes for easy access
        $request->attributes->set('tenant_id', $user->tenant_id);
        
        // Store tenant ID in app config for global access
        config(['app.tenant_id' => $user->tenant_id]);

        return $next($request);
    }
}
