<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\Contracts\TenantServiceInterface;
use Modules\Tenant\Infrastructure\Http\Resources\TenantResource;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantServiceInterface $tenantService,
    ) {}

    public function index(): JsonResponse
    {
        $tenants = $this->tenantService->getAllTenants();

        return response()->json(TenantResource::collection(collect($tenants)));
    }

    public function show(string $id): JsonResponse
    {
        $tenant = $this->tenantService->getTenant($id);

        return response()->json(new TenantResource($tenant));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'domain'   => 'required|string|max:255|unique:tenants,domain',
            'slug'     => 'required|string|max:255|unique:tenants,slug',
            'status'   => 'sometimes|string',
            'plan'     => 'sometimes|string',
            'settings' => 'sometimes|array',
            'metadata' => 'sometimes|array',
        ]);

        $tenant = $this->tenantService->createTenant($data);

        return response()->json(new TenantResource($tenant), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'domain'   => 'sometimes|string|max:255|unique:tenants,domain,' . $id,
            'slug'     => 'sometimes|string|max:255|unique:tenants,slug,' . $id,
            'status'   => 'sometimes|string',
            'plan'     => 'sometimes|string',
            'settings' => 'sometimes|array',
            'metadata' => 'sometimes|array',
        ]);

        $tenant = $this->tenantService->updateTenant($id, $data);

        return response()->json(new TenantResource($tenant));
    }

    public function destroy(string $id): JsonResponse
    {
        $this->tenantService->deleteTenant($id);

        return response()->json(null, 204);
    }
}
