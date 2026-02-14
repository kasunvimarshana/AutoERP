<?php

namespace App\Core\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantAware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        $requestedTenant = $request->header('X-Tenant-ID');

        if ($requestedTenant && $requestedTenant !== $user->tenant_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
