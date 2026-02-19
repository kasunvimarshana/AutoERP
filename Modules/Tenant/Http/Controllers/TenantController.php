<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Http\Requests\StoreTenantRequest;
use Modules\Tenant\Http\Requests\UpdateTenantRequest;
use Modules\Tenant\Http\Resources\TenantResource;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Repositories\TenantRepository;
use Modules\Tenant\Services\TenantService;

/**
 * TenantController
 *
 * Manages tenant CRUD operations (admin only for create/delete)
 */
class TenantController extends ApiController
{
    public function __construct(
        protected TenantService $tenantService,
        protected TenantRepository $tenantRepository
    ) {}

    /**
     * Display a listing of tenants
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);
        $filters = array_filter([
            'active' => request()->input('active'),
            'search' => request()->input('search'),
            'sort_by' => request()->input('sort_by', 'created_at'),
            'sort_order' => request()->input('sort_order', 'desc'),
        ], fn($value) => $value !== null);
        $perPage = request()->input('per_page', 15);
        $tenants = $this->tenantRepository->findWithFilters($filters, $perPage);
        return $this->paginated($tenants, TenantResource::class);
    }

    /**
     * Store a newly created tenant
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);
        $data = $request->validated();
        $tenant = $this->tenantService->createTenant($data);
        return $this->created(new TenantResource($tenant), 'Tenant created successfully');
    }

    /**
     * Display the specified tenant
     */
    public function show(string $id): JsonResponse
    {
        $tenant = $this->tenantRepository->find($id);
        if (! $tenant) {
            return $this->notFound('Tenant not found');
        }
        $this->authorize('view', $tenant);
        return $this->success(new TenantResource($tenant));
    }

    /**
     * Update the specified tenant
     */
    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        $tenant = $this->tenantRepository->find($id);
        if (! $tenant) {
            return $this->notFound('Tenant not found');
        }
        $this->authorize('update', $tenant);
        $updatedTenant = $this->tenantService->updateTenant($id, $request->validated());
        return $this->success(new TenantResource($updatedTenant), 'Tenant updated successfully');
    }

    /**
     * Remove the specified tenant (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = $this->tenantRepository->find($id);
        if (! $tenant) {
            return $this->notFound('Tenant not found');
        }
        $this->authorize('delete', $tenant);
        $this->tenantService->deleteTenant($id);
        return $this->success(null, 'Tenant deleted successfully');
    }

    /**
     * Restore a soft-deleted tenant
     */
    public function restore(string $id): JsonResponse
    {
        $tenant = $this->tenantRepository->findWithTrashed($id);
        if (! $tenant) {
            return $this->notFound('Tenant not found');
        }
        $this->authorize('restore', $tenant);
        $restoredTenant = $this->tenantService->restoreTenant($id);
        return $this->success(new TenantResource($restoredTenant), 'Tenant restored successfully');
    }
}
