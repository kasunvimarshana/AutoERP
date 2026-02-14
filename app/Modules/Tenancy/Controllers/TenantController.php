<?php

namespace App\Modules\Tenancy\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tenancy\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant Controller
 *
 * Handles HTTP requests for tenant management
 * Thin controller delegating to service layer
 *
 * @OA\Tag(name="Tenants", description="Tenant management endpoints")
 */
class TenantController extends Controller
{
    protected TenantService $service;

    public function __construct(TenantService $service)
    {
        $this->service = $service;
    }

    /**
     * Get all tenants
     *
     * @OA\Get(
     *     path="/api/v1/tenants",
     *     tags={"Tenants"},
     *     summary="Get all tenants",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $tenants = $this->service->getPaginated($perPage);

            return response()->json([
                'success' => true,
                'data' => $tenants,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single tenant
     *
     * @OA\Get(
     *     path="/api/v1/tenants/{id}",
     *     tags={"Tenants"},
     *     summary="Get tenant by ID",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $tenant = $this->service->find($id);

            if (! $tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $tenant,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new tenant
     *
     * @OA\Post(
     *     path="/api/v1/tenants",
     *     tags={"Tenants"},
     *     summary="Create new tenant",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'subdomain' => 'required|string|max:255|unique:tenants',
                'domain' => 'nullable|string|max:255',
                'is_active' => 'boolean',
            ]);

            $tenant = $this->service->createTenant($validated);

            return response()->json([
                'success' => true,
                'data' => $tenant,
                'message' => 'Tenant created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update tenant
     *
     * @OA\Put(
     *     path="/api/v1/tenants/{id}",
     *     tags={"Tenants"},
     *     summary="Update tenant",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'subdomain' => 'sometimes|string|max:255|unique:tenants,subdomain,'.$id,
                'domain' => 'nullable|string|max:255',
                'is_active' => 'sometimes|boolean',
            ]);

            $result = $this->service->update($id, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Tenant updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Delete tenant
     *
     * @OA\Delete(
     *     path="/api/v1/tenants/{id}",
     *     tags={"Tenants"},
     *     summary="Delete tenant",
     *     security={{"sanctum": {}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true),
     *
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->service->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Tenant deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
