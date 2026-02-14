<?php

namespace App\Modules\Tenant\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Tenant\Services\TenantService;
use App\Modules\Tenant\DTOs\TenantDTO;
use App\Modules\Tenant\Http\Requests\StoreTenantRequest;
use App\Modules\Tenant\Http\Requests\UpdateTenantRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Tenants",
 *     description="Tenant management endpoints"
 * )
 */
class TenantController extends BaseController
{
    protected TenantService $service;

    public function __construct(TenantService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tenants",
     *     tags={"Tenants"},
     *     summary="List all tenants",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $tenants = $this->service->paginate($perPage);
            
            return $this->successResponse($tenants, 'Tenants retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/tenants",
     *     tags={"Tenants"},
     *     summary="Create a new tenant",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "name", "email", "domain"},
     *             @OA\Property(property="id", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="domain", type="string"),
     *             @OA\Property(property="plan", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tenant created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Tenant")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        try {
            $dto = TenantDTO::fromArray($request->validated());
            $tenant = $this->service->createTenant($dto);
            
            return $this->successResponse($tenant, 'Tenant created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/tenants/{id}",
     *     tags={"Tenants"},
     *     summary="Get tenant by ID",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Tenant")
     *     ),
     *     @OA\Response(response=404, description="Tenant not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $tenant = $this->service->findById($id, ['domains']);
            
            if (!$tenant) {
                return $this->errorResponse('Tenant not found', 404);
            }
            
            return $this->successResponse($tenant, 'Tenant retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/tenants/{id}",
     *     tags={"Tenants"},
     *     summary="Update tenant",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="plan", type="string"),
     *             @OA\Property(property="status", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tenant updated successfully"
     *     ),
     *     @OA\Response(response=404, description="Tenant not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateTenantRequest $request, string $id): JsonResponse
    {
        try {
            $dto = TenantDTO::fromArray($request->validated());
            $updated = $this->service->updateTenant($id, $dto);
            
            if (!$updated) {
                return $this->errorResponse('Failed to update tenant', 500);
            }
            
            return $this->successResponse(null, 'Tenant updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/tenants/{id}",
     *     tags={"Tenants"},
     *     summary="Delete tenant",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tenant deleted successfully"
     *     ),
     *     @OA\Response(response=404, description="Tenant not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->service->deleteTenant($id);
            
            if (!$deleted) {
                return $this->errorResponse('Failed to delete tenant', 500);
            }
            
            return $this->successResponse(null, 'Tenant deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
