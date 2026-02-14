<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to identify and set the current tenant
 * 
 * @package App\Http\Middleware
 */
class TenantIdentification
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->getTenantIdentifier($request);
        
        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant identifier not found'
            ], 400);
        }
        
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid tenant'
            ], 404);
        }
        
        $tenant->makeCurrent();
        
        return $next($request);
    }

    /**
     * Get tenant identifier from request
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTenantIdentifier(Request $request): ?string
    {
        // Try from header first
        if ($request->hasHeader('X-Tenant-ID')) {
            return $request->header('X-Tenant-ID');
        }
        
        // Try from subdomain
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        if (count($parts) > 2) {
            return $parts[0];
        }
        
        // Try from route parameter
        return $request->route('tenant');
    }
}
