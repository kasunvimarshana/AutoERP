<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     *
     * Extract tenant from subdomain, header, or authenticated user
     * and set it in the application context.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to get tenant from header first (for API requests)
        if ($tenantId = $request->header('X-Tenant-ID')) {
            $tenant = Tenant::find($tenantId);
        }
        // Try to get tenant from subdomain
        elseif ($subdomain = $this->extractSubdomain($request)) {
            $tenant = Tenant::where('subdomain', $subdomain)
                ->where('status', 'active')
                ->first();
        }
        // Try to get tenant from authenticated user
        elseif ($user = $request->user()) {
            $tenant = $user->tenant;
        }

        if (isset($tenant) && $tenant) {
            // Set tenant in application context
            app()->instance('tenant', $tenant);
            
            // Set tenant ID in request for easy access
            $request->attributes->set('tenant_id', $tenant->id);

            // For schema-per-tenant, set the search path
            if ($tenant->isolation_strategy === 'schema' && $tenant->database_name) {
                \DB::statement("SET search_path TO {$tenant->database_name}, public");
            }
        }

        return $next($request);
    }

    /**
     * Extract subdomain from request host.
     */
    private function extractSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        // If we have at least 3 parts (subdomain.domain.tld), return the subdomain
        if (count($parts) >= 3) {
            return $parts[0];
        }

        return null;
    }
}
