<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\DTOs\TenantData;
use Modules\Tenant\Application\Services\TenantService;
use Modules\Tenant\Domain\Exceptions\TenantRecordNotFoundException;
use Modules\Tenant\Presentation\Http\Controllers\Concerns\HandlesTenantHttp;
use Modules\Tenant\Presentation\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Presentation\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantResource;

class TenantController extends Controller
{
    use HandlesTenantHttp;

    public function __construct(private readonly TenantService $tenants) {}

    public function index(Request $request): mixed
    {
        return TenantResource::collection($this->tenants->listTenants(
            $this->filters($request, ['slug', 'status', 'tenant_plan_id', 'currency_id']),
            $this->perPage($request),
        ));
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenants->createTenant(TenantData::fromArray($request->validated()));

        return (new TenantResource($tenant))->response()->setStatusCode(201);
    }

    public function show(int|string $tenant): TenantResource|JsonResponse
    {
        try {
            return new TenantResource($this->tenants->findTenant($tenant));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateTenantRequest $request, int|string $tenant): TenantResource|JsonResponse
    {
        try {
            return new TenantResource($this->tenants->updateTenant($tenant, TenantData::fromArray($request->validated())));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant): JsonResponse
    {
        try {
            $this->tenants->deleteTenant($tenant);

            return response()->json(null, 204);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
