<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\DTOs\TenantPlanData;
use Modules\Tenant\Application\Services\TenantService;
use Modules\Tenant\Domain\Exceptions\TenantRecordNotFoundException;
use Modules\Tenant\Presentation\Http\Controllers\Concerns\HandlesTenantHttp;
use Modules\Tenant\Presentation\Http\Requests\StoreTenantPlanRequest;
use Modules\Tenant\Presentation\Http\Requests\UpdateTenantPlanRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantPlanResource;

class TenantPlanController extends Controller
{
    use HandlesTenantHttp;

    public function __construct(private readonly TenantService $tenants) {}

    public function index(Request $request): mixed
    {
        return TenantPlanResource::collection($this->tenants->listPlans(
            $this->filters($request, ['slug', 'billing_interval', 'is_active']),
            $this->perPage($request),
        ));
    }

    public function store(StoreTenantPlanRequest $request): JsonResponse
    {
        $plan = $this->tenants->createPlan(TenantPlanData::fromArray($request->validated()));

        return (new TenantPlanResource($plan))->response()->setStatusCode(201);
    }

    public function show(int|string $plan): TenantPlanResource|JsonResponse
    {
        try {
            return new TenantPlanResource($this->tenants->findPlan($plan));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateTenantPlanRequest $request, int|string $plan): TenantPlanResource|JsonResponse
    {
        try {
            return new TenantPlanResource($this->tenants->updatePlan($plan, TenantPlanData::fromArray($request->validated())));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $plan): JsonResponse
    {
        try {
            $this->tenants->deletePlan($plan);

            return response()->json(null, 204);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    protected function filters(Request $request, array $allowed): array
    {
        $filters = collect($request->only($allowed))
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();

        if (array_key_exists('is_active', $filters)) {
            $filters['is_active'] = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return array_filter($filters, fn (mixed $value): bool => $value !== null);
    }
}
