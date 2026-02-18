<?php

declare(strict_types=1);

namespace Modules\Tenancy\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenancy\Http\Requests\StoreTenantRequest;
use Modules\Tenancy\Http\Requests\UpdateTenantRequest;
use Modules\Tenancy\Http\Resources\TenantResource;
use Modules\Tenancy\Services\TenantService;

/**
 * Tenant API Controller
 *
 * Handles HTTP requests for tenant management.
 * Provides REST API endpoints for CRUD operations and tenant status management.
 */
class TenantController extends Controller
{
    /**
     * TenantController constructor.
     */
    public function __construct(
        protected TenantService $tenantService
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of tenants.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Tenancy\Entities\Tenant::class);

        $perPage = $request->input('per_page', 15);
        $tenants = $this->tenantService->getPaginated($perPage);

        return TenantResource::collection($tenants)->response();
    }

    /**
     * Store a newly created tenant.
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', \Modules\Tenancy\Entities\Tenant::class);

        $tenant = $this->tenantService->create($request->validated());

        return (new TenantResource($tenant))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified tenant.
     */
    public function show(int $id): JsonResponse
    {
        $tenant = $this->tenantService->findById($id);

        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $this->authorize('view', $tenant);

        return (new TenantResource($tenant))->response();
    }

    /**
     * Update the specified tenant.
     */
    public function update(UpdateTenantRequest $request, int $id): JsonResponse
    {
        $tenant = $this->tenantService->findById($id);

        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $this->authorize('update', $tenant);

        $tenant = $this->tenantService->update($id, $request->validated());

        return (new TenantResource($tenant))->response();
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(int $id): JsonResponse
    {
        $tenant = $this->tenantService->findById($id);

        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $this->authorize('delete', $tenant);

        $deleted = $this->tenantService->delete($id);

        if (! $deleted) {
            return response()->json(['message' => 'Failed to delete tenant'], 500);
        }

        return response()->json(['message' => 'Tenant deleted successfully'], 200);
    }

    /**
     * Search tenants by name, slug, or domain.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Tenancy\Entities\Tenant::class);

        $query = $request->input('q', '');
        $tenants = $this->tenantService->search($query);

        return TenantResource::collection($tenants)->response();
    }

    /**
     * Get all active tenants.
     */
    public function active(): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Tenancy\Entities\Tenant::class);

        $tenants = $this->tenantService->getActiveTenants();

        return TenantResource::collection($tenants)->response();
    }

    /**
     * Activate a tenant.
     */
    public function activate(int $id): JsonResponse
    {
        $tenant = $this->tenantService->findById($id);

        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $this->authorize('activate', $tenant);

        $tenant = $this->tenantService->activate($id);

        return (new TenantResource($tenant))->response();
    }

    /**
     * Deactivate a tenant.
     */
    public function deactivate(int $id): JsonResponse
    {
        $tenant = $this->tenantService->findById($id);

        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $this->authorize('deactivate', $tenant);

        $tenant = $this->tenantService->deactivate($id);

        return (new TenantResource($tenant))->response();
    }

    /**
     * Suspend a tenant.
     */
    public function suspend(int $id): JsonResponse
    {
        $tenant = $this->tenantService->findById($id);

        if (! $tenant) {
            return response()->json(['message' => 'Tenant not found'], 404);
        }

        $this->authorize('suspend', $tenant);

        $tenant = $this->tenantService->suspend($id);

        return (new TenantResource($tenant))->response();
    }
}
