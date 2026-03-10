<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Infrastructure\MultiTenant\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ResolveTenant
 *
 * Resolves the tenant context from the incoming request and bootstraps
 * all tenant-specific runtime configurations (database, cache, queue, mail)
 * before the request reaches the controller.
 *
 * This middleware must be applied globally or to the 'api' middleware group.
 */
class ResolveTenant
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Routes that don't require a tenant context (e.g. health checks)
        if ($this->isExcluded($request)) {
            return $next($request);
        }

        $tenantId = $this->tenantManager->resolveFromRequest($request);

        if ($tenantId === null) {
            return response()->json([
                'message' => 'Tenant context could not be resolved. '
                           . 'Provide X-Tenant-ID or X-Tenant-Slug header.',
                'error'   => true,
            ], 400);
        }

        // Make tenant ID available to downstream layers
        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }

    /**
     * Paths that are exempt from tenant resolution.
     *
     * @param  Request $request
     * @return bool
     */
    private function isExcluded(Request $request): bool
    {
        $excluded = [
            'api/health',
        ];

        foreach ($excluded as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }
}
