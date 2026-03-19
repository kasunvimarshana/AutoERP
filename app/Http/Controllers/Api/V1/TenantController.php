<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\AuthException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class TenantController extends Controller
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/admin/tenants",
     *     summary="Create a new tenant",
     *     tags={"Tenants"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function store(CreateTenantRequest $request): JsonResponse
    {
        $tenant = $this->tenantRepository->create($request->validated());

        return response()->json([
            'success' => true,
            'data'    => new TenantResource($tenant),
            'message' => 'Tenant created successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/admin/tenants/{tenantId}",
     *     summary="Get a tenant by ID",
     *     tags={"Tenants"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function show(Request $request, string $tenantId): JsonResponse
    {
        $tenant = $this->tenantRepository->findById($tenantId);

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'TENANT_NOT_FOUND', 'message' => 'Tenant not found.'],
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data'    => new TenantResource($tenant),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/admin/tenants/{tenantId}",
     *     summary="Update a tenant",
     *     tags={"Tenants"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function update(UpdateTenantRequest $request, string $tenantId): JsonResponse
    {
        $tenant = $this->tenantRepository->findById($tenantId);

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'TENANT_NOT_FOUND', 'message' => 'Tenant not found.'],
            ], Response::HTTP_NOT_FOUND);
        }

        $updated = $this->tenantRepository->update($tenantId, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => new TenantResource($updated),
            'message' => 'Tenant updated successfully.',
        ]);
    }
}
