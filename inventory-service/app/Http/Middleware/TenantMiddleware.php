<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves tenant from X-Tenant-ID header and makes it
 * available on the request attributes for downstream use.
 */
final class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (!$tenantId) {
            return response()->json(['message' => 'Missing X-Tenant-ID header.'], 403);
        }

        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }
}
