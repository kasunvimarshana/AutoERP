<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAwareMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure user has tenant_id
        if (auth()->check() && !auth()->user()->tenant_id) {
            return response()->json([
                'message' => 'User not associated with any tenant'
            ], 403);
        }

        // Set tenant context for the request
        if (auth()->check()) {
            config(['app.current_tenant_id' => auth()->user()->tenant_id]);
        }

        return $next($request);
    }
}
