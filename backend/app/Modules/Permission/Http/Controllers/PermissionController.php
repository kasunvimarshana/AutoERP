<?php

namespace App\Modules\Permission\Http\Controllers;

use App\Base\BaseController;
use App\Modules\Permission\Services\PermissionService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="Permissions",
 *     description="Permission management endpoints"
 * )
 */
class PermissionController extends BaseController
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/permissions",
     *     tags={"Permissions"},
     *     summary="List all permissions",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="grouped",
     *         in="query",
     *         description="Group permissions by module",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permissions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Permission")
     *             )
     *         )
     *     )
     * )
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            if ($request->boolean('grouped')) {
                $permissions = $this->permissionService->getGrouped();
                return $this->successResponse($permissions, 'Permissions retrieved successfully (grouped by module)');
            }
            
            $permissions = $this->permissionService->getAllPermissions();
            
            return $this->successResponse($permissions, 'Permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/permissions/{id}",
     *     tags={"Permissions"},
     *     summary="Get permission by ID",
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
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Permission")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Permission not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $permission = $this->permissionService->getPermissionById($id);
            
            if (!$permission) {
                return $this->errorResponse('Permission not found', 404);
            }
            
            return $this->successResponse($permission, 'Permission retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
