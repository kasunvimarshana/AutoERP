<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Support\Facades\App;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $tenantId = $request->header('X-Tenant-ID')
            ?? $request->query('tenant_id')
            ?? ($request->user() ? $request->user()->tenant_id : null);

        if (!$tenantId) {
            return response()->json(['error' => 'Tenant not specified'], 400);
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant || !$tenant->is_active) {
            return response()->json(['error' => 'Invalid or inactive tenant'], 403);
        }

        App::instance('tenant', $tenant);

        return $next($request);
    }
}
