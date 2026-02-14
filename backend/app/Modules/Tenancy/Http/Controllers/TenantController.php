<?php

namespace App\Modules\Tenancy\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Tenancy\Services\TenantService;
use App\Modules\Tenancy\Requests\CreateTenantRequest;
use App\Modules\Tenancy\Requests\UpdateTenantRequest;
use App\Modules\Tenancy\Resources\TenantResource;
use Illuminate\Http\JsonResponse;

/**
 * Tenant Controller
 * 
 * @package App\Modules\Tenancy\Http\Controllers
 */
class TenantController extends BaseController
{
    protected TenantService $service;

    public function __construct(TenantService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        $tenants = $this->service->getAllTenants();
        return $this->collection(TenantResource::collection($tenants));
    }

    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->service->createTenant($request->validated());
        return $this->created(new TenantResource($tenant));
    }

    public function show(int $id): JsonResponse
    {
        $tenant = $this->service->getTenant($id);
        
        if (!$tenant) {
            return $this->notFound();
        }
        
        return $this->resource(new TenantResource($tenant));
    }

    public function update(UpdateTenantRequest $request, int $id): JsonResponse
    {
        $result = $this->service->updateTenant($id, $request->validated());
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->success(null, 'Tenant updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $result = $this->service->deleteTenant($id);
        
        if (!$result) {
            return $this->notFound();
        }
        
        return $this->noContent();
    }
}
