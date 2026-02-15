<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            // Set tenant context globally for this request
            app()->instance('tenant_id', $user->tenant_id);
            config(['app.current_tenant_id' => $user->tenant_id]);
            
            // Set organization and branch context if available
            if ($user->organization_id) {
                app()->instance('organization_id', $user->organization_id);
                config(['app.current_organization_id' => $user->organization_id]);
            }
            
            if ($user->branch_id) {
                app()->instance('branch_id', $user->branch_id);
                config(['app.current_branch_id' => $user->branch_id]);
            }
        }

        return $next($request);
    }
}
