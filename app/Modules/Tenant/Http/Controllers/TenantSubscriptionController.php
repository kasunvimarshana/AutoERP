<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Http\Requests\AssignTenantSubscriptionRequest;
use Modules\Tenant\Http\Resources\TenantSubscriptionResource;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Services\Subscriptions\AssignTenantSubscriptionService;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionReadinessService;

final class TenantSubscriptionController extends Controller
{
    public function __construct(
        private readonly AssignTenantSubscriptionService $assignments,
        private readonly TenantSubscriptionReadinessService $readiness,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function current(int $tenant): JsonResponse|TenantSubscriptionResource
    {
        $subscription = $this->executionContext->runForTenant(
            $tenant,
            fn () => $this->subscriptions->findCurrentByTenant($tenant),
        );

        return $subscription === null
            ? response()->json(['data' => null])
            : new TenantSubscriptionResource($subscription);
    }

    public function readiness(int $tenant, int $tenantPlanRevision): JsonResponse
    {
        return response()->json([
            'data' => $this->readiness->inspect($tenant, $tenantPlanRevision),
        ]);
    }

    public function assign(
        AssignTenantSubscriptionRequest $request,
        int $tenant,
    ): JsonResponse|TenantSubscriptionResource {
        $result = $this->assignments->execute($tenant, $request->validated());

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantSubscriptionResource($result->valueOrFail());
    }
}
