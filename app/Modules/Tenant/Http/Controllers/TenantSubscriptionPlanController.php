<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Tenant\Http\Requests\ListTenantPlanRequest;
use Modules\Tenant\Http\Resources\TenantPlanResource;
use Modules\Tenant\Http\Resources\TenantPlanRevisionResource;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Services\Plans\GetTenantPlanService;
use Modules\Tenant\Services\Plans\ListTenantPlanRevisionsService;
use Modules\Tenant\Services\Plans\ListTenantPlansService;

/** Minimal active-plan directory owned by subscription management. */
final class TenantSubscriptionPlanController extends Controller
{
    public function __construct(
        private readonly ListTenantPlansService $listPlans,
        private readonly GetTenantPlanService $getPlan,
        private readonly ListTenantPlanRevisionsService $listRevisions,
    ) {}

    public function index(ListTenantPlanRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $filters['is_active'] = true;
        $result = $this->listPlans->execute($filters);
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        $page = $result->valueOrFail();
        abort_unless($page instanceof PagedResult, 500, 'Unexpected subscription plan list response.');

        return response()->json([
            'data' => TenantPlanResource::collection($page->items)->resolve(),
            'meta' => $page->paginationMeta(),
        ]);
    }

    public function show(int $tenantPlan): JsonResponse|TenantPlanResource
    {
        $result = $this->getPlan->execute($tenantPlan);

        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantPlanResource($result->valueOrFail());
    }

    public function revisions(int $tenantPlan): JsonResponse
    {
        $result = $this->listRevisions->execute($tenantPlan);
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        return response()->json([
            'data' => TenantPlanRevisionResource::collection($result->valueOrFail())->resolve(),
        ]);
    }
}
