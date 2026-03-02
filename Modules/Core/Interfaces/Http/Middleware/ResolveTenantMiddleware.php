<?php

declare(strict_types=1);

namespace Modules\Core\Interfaces\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId !== null) {
            app()->instance('current.tenant.id', $tenantId);
        }

        return $next($request);
    }

    private function resolveTenantId(Request $request): ?int
    {
        // 1. From authenticated user JWT/Sanctum token
        if ($request->user() !== null && isset($request->user()->tenant_id)) {
            return (int) $request->user()->tenant_id;
        }

        // 2. From X-Tenant-ID header
        $header = $request->header('X-Tenant-ID');
        if ($header !== null && is_numeric($header)) {
            return (int) $header;
        }

        // 3. From subdomain
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0] ?? null;
        if ($subdomain !== null && $subdomain !== 'www' && $subdomain !== 'api') {
            // Lookup tenant by subdomain slug (would normally query DB)
            // For now, return null - concrete implementation in TenantMiddleware
        }

        return null;
    }
}
