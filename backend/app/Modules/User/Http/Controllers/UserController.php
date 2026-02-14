<?php

namespace App\Modules\User\Http\Controllers;

use App\Base\BaseController;
use App\Modules\User\Services\UserService;
use App\Modules\User\DTOs\UserDTO;
use App\Modules\User\Http\Requests\StoreUserRequest;
use App\Modules\User\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 */
class UserController extends BaseController
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="List all users",
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
            $users = $this->service->paginate($perPage, ['tenant', 'roles', 'permissions']);
            
            return $this->successResponse($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tenant_id", "name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="tenant_id", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $dto = UserDTO::fromArray($request->validated());
            $user = $this->service->createUser($dto);
            
            return $this->successResponse($user, 'User created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Get user by ID",
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
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->service->findById($id, ['tenant', 'roles', 'permissions']);
            
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }
            
            return $this->successResponse($user, 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Update user",
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
     *             @OA\Property(property="tenant_id", type="string"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        try {
            $dto = UserDTO::fromArray($request->validated());
            $updated = $this->service->updateUser($id, $dto);
            
            if (!$updated) {
                return $this->errorResponse('Failed to update user', 500);
            }
            
            return $this->successResponse(null, 'User updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Delete user",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->service->deleteUser($id);
            
            if (!$deleted) {
                return $this->errorResponse('Failed to delete user', 500);
            }
            
            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/{id}/assign-role",
     *     tags={"Users"},
     *     summary="Assign role to user",
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
     *             required={"role"},
     *             @OA\Property(property="role", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role assigned successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function assignRole(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'role' => ['required', 'string', 'exists:roles,name'],
            ]);
            
            $result = $this->service->assignRole($id, $request->role);
            
            if (!$result) {
                return $this->errorResponse('Failed to assign role', 500);
            }
            
            return $this->successResponse(null, 'Role assigned successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/{id}/remove-role",
     *     tags={"Users"},
     *     summary="Remove role from user",
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
     *             required={"role"},
     *             @OA\Property(property="role", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role removed successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function removeRole(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'role' => ['required', 'string', 'exists:roles,name'],
            ]);
            
            $result = $this->service->removeRole($id, $request->role);
            
            if (!$result) {
                return $this->errorResponse('Failed to remove role', 500);
            }
            
            return $this->successResponse(null, 'Role removed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/{id}/sync-permissions",
     *     tags={"Users"},
     *     summary="Sync permissions for user",
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
     *             required={"permissions"},
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permissions synced successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function syncPermissions(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'permissions' => ['required', 'array'],
                'permissions.*' => ['string', 'exists:permissions,name'],
            ]);
            
            $result = $this->service->syncPermissions($id, $request->permissions);
            
            if (!$result) {
                return $this->errorResponse('Failed to sync permissions', 500);
            }
            
            return $this->successResponse(null, 'Permissions synced successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/users/{id}/restore",
     *     tags={"Users"},
     *     summary="Restore a soft-deleted user",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User restored successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $restored = $this->service->restore($id);
            
            if (!$restored) {
                return $this->errorResponse('Failed to restore user', 500);
            }
            
            return $this->successResponse(null, 'User restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
