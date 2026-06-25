<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Result;
use Modules\Tenant\Http\Requests\AssignTenantSubscriptionRequest;
use Modules\Tenant\Http\Requests\CancelTenantSubscriptionRequest;
use Modules\Tenant\Http\Requests\CorrectTenantSubscriptionRequest;
use Modules\Tenant\Http\Requests\ExtendTenantSubscriptionRequest;
use Modules\Tenant\Http\Requests\ListTenantSubscriptionHistoryRequest;
use Modules\Tenant\Http\Requests\RenewTenantSubscriptionRequest;
use Modules\Tenant\Http\Resources\TenantSubscriptionResource;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Repositories\TenantSubscriptionRepositoryInterface;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionLifecycleService;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionPolicy;
use Modules\Tenant\Services\Subscriptions\TenantSubscriptionReadinessService;

final class TenantSubscriptionController extends Controller
{
    public function __construct(
        private readonly TenantSubscriptionLifecycleService $lifecycle,
        private readonly TenantSubscriptionReadinessService $readiness,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantSubscriptionRepositoryInterface $subscriptions,
        private readonly TenantSubscriptionPolicy $policy,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function current(int $tenant): JsonResponse|TenantSubscriptionResource
    {
        $this->requireTenant($tenant);
        $subscription = $this->executionContext->runForTenant(
            $tenant,
            fn () => $this->subscriptions->findCurrentByTenant($tenant),
        );

        return $subscription === null
            ? response()->json(['data' => null])
            : new TenantSubscriptionResource($this->withEffectiveStatus($subscription));
    }

    public function history(ListTenantSubscriptionHistoryRequest $request, int $tenant): JsonResponse
    {
        $this->requireTenant($tenant);
        $page = $this->executionContext->runForTenant(
            $tenant,
            fn () => $this->subscriptions->pageHistory(
                $tenant,
                (int) ($request->validated('per_page') ?? config('tenant.pagination.default_per_page', 20)),
                (int) ($request->validated('page') ?? 1),
            ),
        );

        return response()->json([
            'data' => TenantSubscriptionResource::collection($page->items)->resolve(),
            'meta' => $page->paginationMeta(),
        ]);
    }

    public function readiness(int $tenant, int $tenantPlanRevision): JsonResponse
    {
        return response()->json([
            'data' => $this->readiness->inspect($tenant, $tenantPlanRevision),
        ]);
    }

    public function assign(AssignTenantSubscriptionRequest $request, int $tenant): JsonResponse|TenantSubscriptionResource
    {
        return $this->subscriptionResponse($this->lifecycle->assign($tenant, $request->validated()));
    }

    public function renew(RenewTenantSubscriptionRequest $request, int $tenant): JsonResponse|TenantSubscriptionResource
    {
        return $this->subscriptionResponse($this->lifecycle->renew($tenant, $request->validated()));
    }

    public function extend(ExtendTenantSubscriptionRequest $request, int $tenant): JsonResponse|TenantSubscriptionResource
    {
        return $this->subscriptionResponse($this->lifecycle->extend($tenant, $request->validated()));
    }

    public function correct(CorrectTenantSubscriptionRequest $request, int $tenant): JsonResponse|TenantSubscriptionResource
    {
        return $this->subscriptionResponse($this->lifecycle->correct($tenant, $request->validated()));
    }

    public function cancel(CancelTenantSubscriptionRequest $request, int $tenant): JsonResponse|TenantSubscriptionResource
    {
        return $this->subscriptionResponse($this->lifecycle->cancel($tenant, $request->validated()));
    }

    private function requireTenant(int $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                ->setModel(\Modules\Tenant\Models\TenantModel::class, [$tenantId]);
        }
    }

    private function subscriptionResponse(Result $result): JsonResponse|TenantSubscriptionResource
    {
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        $subscription = $result->valueOrFail();
        abort_unless($subscription instanceof DataRecord, 500, 'Unexpected tenant subscription response.');

        return new TenantSubscriptionResource($this->withEffectiveStatus($subscription));
    }

    /** @return array<string, mixed> */
    private function withEffectiveStatus(DataRecord $subscription): array
    {
        $values = $subscription->toArray();
        $values['effective_status'] = $this->policy->statusAt($values);

        return $values;
    }
}
