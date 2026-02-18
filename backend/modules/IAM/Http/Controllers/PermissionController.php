<?php

namespace Modules\IAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\IAM\DTOs\PermissionDTO;
use Modules\IAM\Http\Requests\StorePermissionRequest;
use Modules\IAM\Services\PermissionService;

class PermissionController extends BaseController
{
    public function __construct(private PermissionService $permissionService) {}

    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     summary="List all permissions",
     *     description="Retrieve all permissions in the system",
     *     operationId="permissionsIndex",
     *     tags={"IAM-Permissions"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Permission")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $permissions = $this->permissionService->getAll();

            return $this->success($permissions);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch permissions: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/permissions",
     *     summary="Create a new permission",
     *     description="Create a new permission with resource and action",
     *     operationId="permissionsStore",
     *     tags={"IAM-Permissions"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Permission creation data",
     *         @OA\JsonContent(ref="#/components/schemas/StorePermissionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Permission")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        try {
            $dto = new PermissionDTO($request->validated());
            $permission = $this->permissionService->create($dto);

            return $this->created($permission, 'Permission created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create permission: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/permissions/{id}",
     *     summary="Get permission by ID",
     *     description="Retrieve a specific permission by its ID",
     *     operationId="permissionsShow",
     *     tags={"IAM-Permissions"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Permission")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $permission = $this->permissionService->find($id);

            if (! $permission) {
                return $this->notFound('Permission not found');
            }

            return $this->success($permission);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch permission: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/permissions/{id}",
     *     summary="Delete permission",
     *     description="Delete a permission from the system",
     *     operationId="permissionsDestroy",
     *     tags={"IAM-Permissions"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->permissionService->delete($id);

            return $this->deleted('Permission deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete permission: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/permissions/by-resource",
     *     summary="Get permissions by resource",
     *     description="Retrieve all permissions for a specific resource",
     *     operationId="permissionsByResource",
     *     tags={"IAM-Permissions"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="resource",
     *         in="query",
     *         description="Resource name (e.g., user, role, product)",
     *         required=true,
     *         @OA\Schema(type="string", example="user")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Permission")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Resource parameter required",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function byResource(Request $request): JsonResponse
    {
        try {
            $resource = $request->input('resource');

            if (! $resource) {
                return $this->validationError('Resource parameter is required', [
                    'resource' => ['The resource field is required'],
                ]);
            }

            $permissions = $this->permissionService->getByResource($resource);

            return $this->success($permissions);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch permissions: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/permissions/grouped",
     *     summary="Get permissions grouped by resource",
     *     description="Retrieve all permissions grouped by their resource type",
     *     operationId="permissionsGrouped",
     *     tags={"IAM-Permissions"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Grouped permissions retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Permissions grouped by resource",
     *                 @OA\Property(
     *                     property="user",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Permission")
     *                 ),
     *                 @OA\Property(
     *                     property="role",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Permission")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function grouped(): JsonResponse
    {
        try {
            $grouped = $this->permissionService->getAllGroupedByResource();

            return $this->success($grouped);
        } catch (\Exception $e) {
            return $this->error('Failed to fetch grouped permissions: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/permissions/bulk",
     *     summary="Create permissions in bulk",
     *     description="Create multiple permissions for a resource with different actions at once",
     *     operationId="permissionsCreateBulk",
     *     tags={"IAM-Permissions"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Bulk permission creation data",
     *         @OA\JsonContent(
     *             required={"resource", "actions"},
     *             @OA\Property(property="resource", type="string", example="product", description="Resource name"),
     *             @OA\Property(
     *                 property="actions",
     *                 type="array",
     *                 minItems=1,
     *                 @OA\Items(type="string", example="create"),
     *                 description="Array of action names (e.g., create, read, update, delete)"
     *             ),
     *             @OA\Property(property="description", type="string", example="Product management permissions", description="Optional description for the permissions")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permissions created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Permission")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User lacks required permission",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function createBulk(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'resource' => ['required', 'string', 'max:255'],
                'actions' => ['required', 'array', 'min:1'],
                'actions.*' => ['string', 'max:255'],
                'description' => ['sometimes', 'string', 'max:500'],
            ]);

            $permissions = $this->permissionService->createBulk(
                $validated['resource'],
                $validated['actions'],
                $validated['description'] ?? null
            );

            return $this->created($permissions, 'Permissions created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create permissions: '.$e->getMessage(), 500);
        }
    }
}
