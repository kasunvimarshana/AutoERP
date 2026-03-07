<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');
        $userId   = $request->header('X-User-ID');

        if (!$tenantId) {
            return response()->json(['message' => 'Missing X-Tenant-ID header.'], 403);
        }

        $request->attributes->set('tenant_id', $tenantId);
        $request->attributes->set('user_id', $userId);

        return $next($request);
    }
}
