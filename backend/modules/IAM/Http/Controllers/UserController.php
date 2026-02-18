<?php

namespace Modules\IAM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\IAM\DTOs\ChangePasswordDTO;
use Modules\IAM\DTOs\UserDTO;
use Modules\IAM\Http\Requests\ChangePasswordRequest;
use Modules\IAM\Http\Requests\StoreUserRequest;
use Modules\IAM\Http\Requests\UpdateUserRequest;
use Modules\IAM\Http\Resources\UserResource;
use Modules\IAM\Services\UserService;

class UserController extends BaseController
{
    public function __construct(private UserService $userService) {}

    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="List all users",
     *     description="Retrieve paginated list of all users",
     *     operationId="usersIndex",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/PaginatedResponse")
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
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $users = $this->userService->getAll($perPage);

            return $this->success(UserResource::collection($users));
        } catch (\Exception $e) {
            return $this->error('Failed to fetch users: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/search",
     *     summary="Search users",
     *     description="Search users by name or email",
     *     operationId="usersSearch",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Search query string",
     *         required=false,
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/PaginatedResponse")
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
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->input('query', '');
            $perPage = $request->integer('per_page', 15);
            $users = $this->userService->search($query, $perPage);

            return $this->success(UserResource::collection($users));
        } catch (\Exception $e) {
            return $this->error('Failed to search users: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create a new user",
     *     description="Create a new user with specified roles and permissions",
     *     operationId="usersStore",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User creation data",
     *         @OA\JsonContent(ref="#/components/schemas/StoreUserRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
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
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $dto = new UserDTO($request->validated());
            $user = $this->userService->create($dto);

            return $this->created(UserResource::make($user), 'User created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to create user: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get user by ID",
     *     description="Retrieve a specific user by their ID",
     *     operationId="usersShow",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
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
            $user = $this->userService->find($id);

            if (! $user) {
                return $this->notFound('User not found');
            }

            return $this->success(UserResource::make($user));
        } catch (\Exception $e) {
            return $this->error('Failed to fetch user: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update user",
     *     description="Update an existing user's information",
     *     operationId="usersUpdate",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="User update data",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateUserRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
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
     *         description="User not found",
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
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $dto = new UserDTO($request->validated());
            $dto->id = $id;
            $user = $this->userService->update($id, $dto);

            return $this->updated(UserResource::make($user), 'User updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update user: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete user",
     *     description="Delete a user from the system",
     *     operationId="usersDestroy",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deleted successfully")
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
     *         description="User not found",
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
            $this->userService->delete($id);

            return $this->deleted('User deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete user: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/profile",
     *     summary="Get authenticated user profile",
     *     description="Retrieve the current user's profile information",
     *     operationId="usersProfile",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
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
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load('roles', 'permissions');

            return $this->success(UserResource::make($user));
        } catch (\Exception $e) {
            return $this->error('Failed to fetch profile: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/profile",
     *     summary="Update authenticated user profile",
     *     description="Update the current user's profile information",
     *     operationId="usersUpdateProfile",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         description="Profile update data",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="avatar", type="string", format="uri", example="https://example.com/avatar.jpg"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="timezone", type="string", example="America/New_York"),
     *             @OA\Property(property="locale", type="string", enum={"en", "es", "fr", "de"}, example="en")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
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
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'avatar' => ['sometimes', 'url'],
                'phone' => ['sometimes', 'string', 'max:20'],
                'timezone' => ['sometimes', 'string', 'timezone'],
                'locale' => ['sometimes', 'string', 'in:en,es,fr,de'],
            ]);

            $user = $this->userService->updateProfile($request->user(), $validated);

            return $this->updated(UserResource::make($user), 'Profile updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to update profile: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users/change-password",
     *     summary="Change password",
     *     description="Change the current user's password",
     *     operationId="usersChangePassword",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Password change data",
     *         @OA\JsonContent(ref="#/components/schemas/ChangePasswordRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password changed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password changed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
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
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $dto = new ChangePasswordDTO($request->validated());
            $this->userService->changePassword($request->user(), $dto);

            return $this->success(null, 'Password changed successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError('Validation failed', $e->errors());
        } catch (\Exception $e) {
            return $this->error('Failed to change password: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users/{id}/activate",
     *     summary="Activate user",
     *     description="Activate a deactivated user account",
     *     operationId="usersActivate",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User activated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User activated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
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
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $user = $this->userService->activate($id);

            return $this->success(UserResource::make($user), 'User activated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to activate user: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users/{id}/deactivate",
     *     summary="Deactivate user",
     *     description="Deactivate an active user account",
     *     operationId="usersDeactivate",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deactivated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deactivated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
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
     *         description="User not found",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $user = $this->userService->deactivate($id);

            return $this->success(UserResource::make($user), 'User deactivated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to deactivate user: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users/{id}/roles/assign",
     *     summary="Assign role to user",
     *     description="Assign a role to a specific user",
     *     operationId="usersAssignRole",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Role assignment data",
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", example="admin", description="Role name to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role assigned successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
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
    public function assignRole(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role' => ['required', 'string', 'exists:roles,name'],
            ]);

            $user = $this->userService->find($id);
            if (! $user) {
                return $this->notFound('User not found');
            }

            $this->userService->assignRole($user, $validated['role']);

            return $this->success(null, 'Role assigned successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to assign role: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users/{id}/roles/remove",
     *     summary="Remove role from user",
     *     description="Remove a role from a specific user",
     *     operationId="usersRemoveRole",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Role removal data",
     *         @OA\JsonContent(
     *             required={"role"},
     *             @OA\Property(property="role", type="string", example="admin", description="Role name to remove")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role removed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
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
    public function removeRole(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role' => ['required', 'string', 'exists:roles,name'],
            ]);

            $user = $this->userService->find($id);
            if (! $user) {
                return $this->notFound('User not found');
            }

            $this->userService->removeRole($user, $validated['role']);

            return $this->success(null, 'Role removed successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to remove role: '.$e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users/{id}/roles/sync",
     *     summary="Synchronize user roles",
     *     description="Replace all user roles with the specified set of roles",
     *     operationId="usersSyncRoles",
     *     tags={"IAM-Users"},
     *     security={{"sanctum_token": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Roles synchronization data",
     *         @OA\JsonContent(
     *             required={"roles"},
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 @OA\Items(type="string", example="admin"),
     *                 description="Array of role names"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles synchronized successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Roles synchronized successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
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
    public function syncRoles(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'roles' => ['required', 'array'],
                'roles.*' => ['string', 'exists:roles,name'],
            ]);

            $user = $this->userService->find($id);
            if (! $user) {
                return $this->notFound('User not found');
            }

            $this->userService->syncRoles($user, $validated['roles']);

            return $this->success(null, 'Roles synchronized successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to sync roles: '.$e->getMessage(), 500);
        }
    }
}
