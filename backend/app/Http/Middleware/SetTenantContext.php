<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetTenantContext Middleware
 * 
 * Sets the tenant context for the current request.
 * This middleware ensures that the current user's tenant_id is available
 * throughout the application for data isolation.
 */
class SetTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Set tenant context if user is authenticated
        if ($user = auth()->user()) {
            // Store tenant_id in the app container for easy access
            app()->instance('tenant_id', $user->tenant_id);
            
            // Set tenant context for Spatie Permission (team_id becomes tenant_id)
            if (method_exists($user, 'setPermissionsTeamId')) {
                $user->setPermissionsTeamId($user->tenant_id);
            }
        }
        
        return $next($request);
    }
}
