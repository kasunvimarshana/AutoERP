<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Service\Application\Contracts\CreateServiceMaintenancePlanServiceInterface;
use Modules\Service\Application\Contracts\FindServiceMaintenancePlanServiceInterface;
use Modules\Service\Domain\Entities\ServiceMaintenancePlan;
use Modules\Service\Infrastructure\Http\Requests\StoreServiceMaintenancePlanRequest;
use Modules\Service\Infrastructure\Http\Resources\ServiceMaintenancePlanResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ServiceMaintenancePlanController extends AuthorizedController
{
    public function __construct(
        private readonly CreateServiceMaintenancePlanServiceInterface $createPlan,
        private readonly FindServiceMaintenancePlanServiceInterface $findPlan,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', ServiceMaintenancePlan::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $result = $this->findPlan->paginate(
            tenantId: $tenantId,
            filters: [],
            perPage: (int) (request()->query('per_page', 15)),
            page: (int) (request()->query('page', 1)),
        );

        return response()->json($result);
    }

    public function store(StoreServiceMaintenancePlanRequest $request): JsonResponse
    {
        $this->authorize('create', ServiceMaintenancePlan::class);

        $plan = $this->createPlan->execute($request->validated());

        return (new ServiceMaintenancePlanResource($plan))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(int $plan): JsonResponse
    {
        $this->authorize('view', ServiceMaintenancePlan::class);

        $tenantId = (int) request()->header('X-Tenant-ID');
        $found = $this->findPlan->findById($tenantId, $plan);

        return (new ServiceMaintenancePlanResource($found))->response();
    }

    public function update(StoreServiceMaintenancePlanRequest $request, int $plan): JsonResponse
    {
        $this->authorize('update', ServiceMaintenancePlan::class);

        return response()->json(['message' => 'Not implemented'], HttpResponse::HTTP_NOT_IMPLEMENTED);
    }

    public function destroy(int $plan): JsonResponse
    {
        $this->authorize('delete', ServiceMaintenancePlan::class);

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }
}
