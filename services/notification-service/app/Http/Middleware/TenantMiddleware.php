<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            return response()->json(['success' => false, 'message' => 'Tenant context required.'], 400);
        }

        $request->attributes->set('tenant_id', $tenantId);
        return $next($request);
    }
}
