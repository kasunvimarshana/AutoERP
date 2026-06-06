<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Tenant\Application\UseCases\Plans\CreateTenantPlanService;
use Modules\Tenant\Application\UseCases\Plans\DeleteTenantPlanService;
use Modules\Tenant\Application\UseCases\Plans\GetTenantPlanService;
use Modules\Tenant\Application\UseCases\Plans\ListTenantPlansService;
use Modules\Tenant\Application\UseCases\Plans\UpdateTenantPlanService;
use Modules\Tenant\Presentation\Http\Requests\ListTenantPlanRequest;
use Modules\Tenant\Presentation\Http\Requests\UpsertTenantPlanRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantPlanResource;

final class TenantPlanController extends Controller
{
    public function __construct(
        private readonly ListTenantPlansService $listPlans,
        private readonly GetTenantPlanService $getPlan,
        private readonly CreateTenantPlanService $createPlan,
        private readonly UpdateTenantPlanService $updatePlan,
        private readonly DeleteTenantPlanService $deletePlan,
    ) {}

    public function index(ListTenantPlanRequest $request): JsonResponse
    {
        $result = $this->listPlans->execute($request->validated());
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $page = $result->valueOrFail();
        if (! $page instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => TenantPlanResource::collection($page->items)->resolve(),
            'meta' => [
                'total' => $page->total,
                'page' => $page->page,
                'per_page' => $page->perPage,
                'page_count' => $page->pageCount(),
                'has_more' => $page->hasMore(),
            ],
        ]);
    }

    public function show(int|string $tenantPlan): JsonResponse|TenantPlanResource
    {
        $result = $this->getPlan->execute($tenantPlan);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TenantPlanResource($result->valueOrFail());
    }

    public function store(UpsertTenantPlanRequest $request): JsonResponse|TenantPlanResource
    {
        $result = $this->createPlan->execute($request->validated());
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TenantPlanResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertTenantPlanRequest $request, int|string $tenantPlan): JsonResponse|TenantPlanResource
    {
        $result = $this->updatePlan->execute($tenantPlan, $request->validated());
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'TENANT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new TenantPlanResource($result->valueOrFail());
    }

    public function destroy(int|string $tenantPlan): JsonResponse
    {
        $result = $this->deletePlan->execute($tenantPlan);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
