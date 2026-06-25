<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Tenant\Services\TenantEntitlementService;
use Symfony\Component\HttpFoundation\Response;

final class RequireTenantFeatureMiddleware
{
    public function __construct(
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly TenantEntitlementService $entitlements,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null || ! $this->entitlements->featureEnabled($tenantId, $feature)) {
            return new JsonResponse([
                'message' => 'This feature is not enabled for the active tenant plan.',
                'code' => 'TENANT_FEATURE_NOT_ENABLED',
                'feature' => $feature,
            ], 403);
        }

        return $next($request);
    }
}
