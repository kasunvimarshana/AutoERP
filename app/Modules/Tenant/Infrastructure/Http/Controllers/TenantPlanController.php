<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Modules\Tenant\Application\Contracts\CreateTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantPlanServiceInterface;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Tenant\Application\Contracts\FindTenantPlansServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantPlanServiceInterface;
use Modules\Tenant\Application\DTOs\TenantPlanData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Entities\TenantPlan;
use Modules\Tenant\Infrastructure\Http\Requests\ListTenantPlanRequest;
use Modules\Tenant\Infrastructure\Http\Requests\StoreTenantPlanRequest;
use Modules\Tenant\Infrastructure\Http\Requests\UpdateTenantPlanRequest;
use Modules\Tenant\Infrastructure\Http\Resources\TenantPlanCollection;
use Modules\Tenant\Infrastructure\Http\Resources\TenantPlanResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantPlanController extends AuthorizedController
{
    public function __construct(
        private readonly FindTenantPlansServiceInterface $findPlansService,
        private readonly CreateTenantPlanServiceInterface $createPlanService,
        private readonly UpdateTenantPlanServiceInterface $updatePlanService,
        private readonly DeleteTenantPlanServiceInterface $deletePlanService
    ) {}

    public function index(ListTenantPlanRequest $request): TenantPlanCollection
    {
        $this->authorize('viewAny', Tenant::class);
        $validated = $request->validated();
        $billingInterval = $validated['billing_interval'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);
        $plans = $this->findPlansService->paginateActive(
            is_string($billingInterval) ? $billingInterval : null,
            $perPage,
            $page
        );

        return new TenantPlanCollection($plans);
    }

    public function show(int $plan): TenantPlanResource
    {
        $this->authorize('viewAny', Tenant::class);
        $planEntity = $this->findPlanOrFail($plan);

        return new TenantPlanResource($planEntity);
    }

    public function store(StoreTenantPlanRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);
        $dto = TenantPlanData::fromArray($request->validated());
        $created = $this->createPlanService->execute($dto->toArray());

        return (new TenantPlanResource($created))->response()->setStatusCode(201);
    }

    public function update(UpdateTenantPlanRequest $request, int $plan): TenantPlanResource
    {
        $this->authorize('update', Tenant::class);
        $this->findPlanOrFail($plan);

        $payload = $request->validated();
        $payload['id'] = $plan;
        $updated = $this->updatePlanService->execute($payload);

        return new TenantPlanResource($updated);
    }

    public function destroy(int $plan): JsonResponse
    {
        $this->authorize('delete', Tenant::class);
        $this->findPlanOrFail($plan);
        $this->deletePlanService->execute(['id' => $plan]);

        return Response::json(['message' => 'Tenant plan deleted successfully']);
    }

    private function findPlanOrFail(int $planId): TenantPlan
    {
        $plan = $this->findPlansService->find($planId);
        if (! $plan) {
            throw new NotFoundHttpException('Tenant plan not found.');
        }

        return $plan;
    }
}
