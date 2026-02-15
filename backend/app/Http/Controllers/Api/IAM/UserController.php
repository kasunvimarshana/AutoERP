<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\IAM;

use App\Http\Controllers\BaseController;
use App\Http\Requests\IAM\StoreUserRequest;
use App\Http\Requests\IAM\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Modules\IAM\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends BaseController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Display a listing of users.
     *
     * @OA\Get(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Get list of users",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->get('per_page', 15);
        $users = $this->userService->getUsers($perPage);

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user.
     *
     * @OA\Post(
     *     path="/api/v1/users",
     *     tags={"Users"},
     *     summary="Create a new user",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="organization_id", type="integer"),
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "suspended"}),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return $this->successResponse(
                new UserResource($user),
                'User created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create user',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Display the specified user.
     *
     * @OA\Get(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Get user details",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUser($id);

        if (!$user) {
            return $this->errorResponse('User not found', [], 404);
        }

        return $this->successResponse(new UserResource($user));
    }

    /**
     * Update the specified user.
     *
     * @OA\Put(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Update user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="organization_id", type="integer"),
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully"
     *     )
     * )
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->userService->updateUser($id, $request->validated());

            return $this->successResponse(
                new UserResource($user),
                'User updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update user',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Remove the specified user.
     *
     * @OA\Delete(
     *     path="/api/v1/users/{id}",
     *     tags={"Users"},
     *     summary="Delete user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);

            return $this->successResponse(
                null,
                'User deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete user',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Restore a soft-deleted user.
     *
     * @OA\Post(
     *     path="/api/v1/users/{id}/restore",
     *     tags={"Users"},
     *     summary="Restore deleted user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User restored successfully"
     *     )
     * )
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $this->userService->restoreUser($id);

            return $this->successResponse(
                null,
                'User restored successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to restore user',
                [$e->getMessage()],
                500
            );
        }
    }

    /**
     * Search users.
     *
     * @OA\Get(
     *     path="/api/v1/users/search",
     *     tags={"Users"},
     *     summary="Search users",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="User name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="User email",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="User status",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="User role",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $criteria = $request->only(['name', 'email', 'status', 'organization_id', 'branch_id', 'role']);
        $perPage = (int) $request->get('per_page', 15);

        $users = $this->userService->searchUsers($criteria, $perPage);

        return UserResource::collection($users);
    }

    /**
     * Assign roles to user.
     *
     * @OA\Post(
     *     path="/api/v1/users/{id}/roles",
     *     tags={"Users"},
     *     summary="Assign roles to user",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"roles"},
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles assigned successfully"
     *     )
     * )
     */
    public function assignRoles(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
        ]);

        try {
            $user = $this->userService->assignRolesToUser($id, $request->input('roles'));

            return $this->successResponse(
                new UserResource($user),
                'Roles assigned successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to assign roles',
                [$e->getMessage()],
                500
            );
        }
    }
}
