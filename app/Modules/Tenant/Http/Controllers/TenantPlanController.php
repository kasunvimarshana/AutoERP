<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantPermission;
use Modules\Tenant\Http\Requests\ListTenantPlanRequest;
use Modules\Tenant\Http\Requests\TenantVersionRequest;
use Modules\Tenant\Http\Requests\UpsertTenantPlanRequest;
use Modules\Tenant\Http\Resources\TenantPlanResource;
use Modules\Tenant\Http\Support\TenantApiResponder;
use Modules\Tenant\Services\Plans\CreateTenantPlanService;
use Modules\Tenant\Services\Plans\DeleteTenantPlanService;
use Modules\Tenant\Services\Plans\GetTenantPlanService;
use Modules\Tenant\Services\Plans\ListTenantPlansService;
use Modules\Tenant\Services\Plans\UpdateTenantPlanService;
use Modules\Tenant\Services\TenantAuthorizationService;

final class TenantPlanController extends Controller
{
    public function __construct(
        private readonly TenantAuthorizationService $authorization,
        private readonly ListTenantPlansService $listPlans,
        private readonly GetTenantPlanService $getPlan,
        private readonly CreateTenantPlanService $createPlan,
        private readonly UpdateTenantPlanService $updatePlan,
        private readonly DeleteTenantPlanService $deactivatePlan,
    ) {}

    public function index(ListTenantPlanRequest $request): JsonResponse
    {
        $this->requirePermission(TenantPermission::PLATFORM_VIEW);

        $result = $this->listPlans->execute($request->validated());
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        $page = $result->valueOrFail();
        abort_unless($page instanceof PagedResult, 500, 'Unexpected tenant plan list response.');

        return response()->json([
            'data' => TenantPlanResource::collection($page->items)->resolve(),
            'meta' => $page->paginationMeta(),
        ]);
    }

    public function show(int|string $tenantPlan): JsonResponse|TenantPlanResource
    {
        $this->requirePermission(TenantPermission::PLATFORM_VIEW);

        return $this->planResponse($this->getPlan->execute($tenantPlan));
    }

    public function store(UpsertTenantPlanRequest $request): JsonResponse|TenantPlanResource
    {
        $this->requirePermission(TenantPermission::PLATFORM_MANAGE_PLANS);

        $result = $this->createPlan->execute($request->validated());
        if ($result->isFailure()) {
            return TenantApiResponder::error($result->errorOrFail());
        }

        return (new TenantPlanResource($result->valueOrFail()))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpsertTenantPlanRequest $request,
        int|string $tenantPlan,
    ): JsonResponse|TenantPlanResource {
        $this->requirePermission(TenantPermission::PLATFORM_MANAGE_PLANS);

        return $this->planResponse(
            $this->updatePlan->execute($tenantPlan, $request->validated()),
        );
    }

    public function deactivate(
        TenantVersionRequest $request,
        int|string $tenantPlan,
    ): JsonResponse|TenantPlanResource {
        $this->requirePermission(TenantPermission::PLATFORM_MANAGE_PLANS);

        return $this->planResponse($this->deactivatePlan->execute(
            $tenantPlan,
            (int) $request->validated('expected_version'),
        ));
    }

    private function planResponse(Result $result): JsonResponse|TenantPlanResource
    {
        return $result->isFailure()
            ? TenantApiResponder::error($result->errorOrFail())
            : new TenantPlanResource($result->valueOrFail());
    }

    private function requirePermission(string $permission): void
    {
        abort_unless(
            $this->authorization->allows($permission),
            403,
            'You are not authorized to perform this action.',
        );
    }
}
