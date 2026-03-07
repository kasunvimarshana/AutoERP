<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Tenant::class);

        $tenants = $this->tenantService->list(
            $request->only(['search', 'is_active', 'sort_by', 'sort_dir']),
            $request->filled('per_page') ? (int) $request->input('per_page') : null,
        );

        return response()->json(TenantResource::collection($tenants)->response()->getData(true));
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', \App\Models\Tenant::class);

        $tenant = $this->tenantService->create($request->validated());

        return response()->json([
            'message' => 'Tenant created successfully.',
            'data' => new TenantResource($tenant),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $tenant = $this->tenantService->findById($id);
        $this->authorize('view', $tenant);

        return response()->json(['data' => new TenantResource($tenant)]);
    }

    public function update(UpdateTenantRequest $request, int $id): JsonResponse
    {
        $tenant = $this->tenantService->findById($id);
        $this->authorize('update', $tenant);

        $updated = $this->tenantService->update($id, $request->validated());

        return response()->json([
            'message' => 'Tenant updated successfully.',
            'data' => new TenantResource($updated),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $tenant = $this->tenantService->findById($id);
        $this->authorize('delete', $tenant);

        $this->tenantService->delete($id);

        return response()->json(['message' => 'Tenant deleted successfully.'], 204);
    }
}
