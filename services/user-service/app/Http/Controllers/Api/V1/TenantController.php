<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\TenantDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Resources\TenantCollection;
use App\Http\Resources\TenantResource;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TenantController extends Controller
{
    public function __construct(private readonly TenantService $tenantService) {}

    /**
     * GET /api/v1/tenants
     */
    public function index(Request $request): TenantCollection
    {
        $sortParam = (string) $request->query('sort', 'created_at');
        $paginator = $this->tenantService->listTenants(
            perPage:  (int) $request->query('per_page', 15),
            filters:  $request->query('filter', []),
            sortBy:   ltrim($sortParam, '-'),
            sortDir:  str_starts_with($sortParam, '-') ? 'desc' : 'asc',
            search:   $request->query('search'),
        );

        return new TenantCollection($paginator);
    }

    /**
     * GET /api/v1/tenants/{id}
     */
    public function show(int $id): JsonResponse
    {
        $tenant = $this->tenantService->getTenant($id);

        if (! $tenant) {
            return response()->json(['error' => 'Tenant not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(new TenantResource($tenant));
    }

    /**
     * POST /api/v1/tenants
     */
    public function store(CreateTenantRequest $request): JsonResponse
    {
        $dto = TenantDTO::fromArray($request->validated());

        try {
            $tenant = $this->tenantService->createTenant($dto);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return response()->json(new TenantResource($tenant), Response::HTTP_CREATED);
    }

    /**
     * PUT /api/v1/tenants/{id}
     */
    public function update(UpdateTenantRequest $request, int $id): JsonResponse
    {
        $existing = $this->tenantService->getTenant($id);

        if (! $existing) {
            return response()->json(['error' => 'Tenant not found'], Response::HTTP_NOT_FOUND);
        }

        $data = array_merge($existing->toArray(), $request->validated());
        $dto  = TenantDTO::fromArray($data);

        try {
            $tenant = $this->tenantService->updateTenant($id, $dto);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return response()->json(new TenantResource($tenant));
    }

    /**
     * DELETE /api/v1/tenants/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->tenantService->deleteTenant($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['error' => 'Tenant not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
