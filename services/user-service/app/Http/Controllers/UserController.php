<?php

namespace App\Http\Controllers;

use App\DTOs\UserDTO;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function __construct(private readonly UserService $userService) {}

    // -------------------------------------------------------------------------
    // GET /api/users
    // -------------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID') ?? $request->user()?->tenant_id;

        $users = $this->userService->getAllUsers($request, $tenantId ? (int) $tenantId : null);

        return $this->paginatedResponse($users, 'Users retrieved');
    }

    // -------------------------------------------------------------------------
    // POST /api/users
    // -------------------------------------------------------------------------

    public function store(CreateUserRequest $request): JsonResponse
    {
        $tenantId = $request->header('X-Tenant-ID') ?? $request->user()?->tenant_id;

        $dto = new UserDTO(
            name:      $request->validated('name'),
            email:     $request->validated('email'),
            password:  $request->validated('password'),
            tenantId:  $tenantId ? (int) $tenantId : null,
            roleIds:   $request->validated('role_ids', []),
            isActive:  $request->validated('is_active', true),
            metadata:  $request->validated('metadata', []),
        );

        try {
            $user = $this->userService->createUser($dto);

            return $this->createdResponse($user, 'User created successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to create user', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/users/{id}
    // -------------------------------------------------------------------------

    public function show(Request $request, int|string $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        // Ensure tenant isolation
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId && (string) $user->tenant_id !== (string) $tenantId) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        return $this->successResponse($user, 'User retrieved');
    }

    // -------------------------------------------------------------------------
    // PUT/PATCH /api/users/{id}
    // -------------------------------------------------------------------------

    public function update(UpdateUserRequest $request, int|string $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        // Tenant isolation check
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId && (string) $user->tenant_id !== (string) $tenantId) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        try {
            $updated = $this->userService->updateUser($id, $request->validated());

            return $this->successResponse($updated, 'User updated successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to update user', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // DELETE /api/users/{id}
    // -------------------------------------------------------------------------

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        if (! $user) {
            return $this->notFoundResponse('User not found');
        }

        // Tenant isolation check
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId && (string) $user->tenant_id !== (string) $tenantId) {
            return $this->forbiddenResponse('Access denied for this tenant');
        }

        // Prevent self-deletion
        if ((string) $request->user()?->id === (string) $id) {
            return $this->errorResponse('You cannot delete your own account', null, 422);
        }

        try {
            $this->userService->deleteUser($id);

            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to delete user', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/users/{id}/roles
    // -------------------------------------------------------------------------

    public function assignRoles(Request $request, int|string $id): JsonResponse
    {
        $request->validate([
            'role_ids'   => ['required', 'array'],
            'role_ids.*' => ['integer'],
        ]);

        try {
            $user = $this->userService->syncUserRoles($id, $request->input('role_ids'));

            return $this->successResponse($user, 'Roles assigned');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to assign roles', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/users/{id}/permissions
    // -------------------------------------------------------------------------

    public function assignPermissions(Request $request, int|string $id): JsonResponse
    {
        $request->validate([
            'permission_ids'   => ['required', 'array'],
            'permission_ids.*' => ['integer'],
        ]);

        try {
            $user = $this->userService->assignPermissionsToUser($id, $request->input('permission_ids'));

            return $this->successResponse($user, 'Permissions assigned');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to assign permissions', $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/users/me  – current user profile
    // -------------------------------------------------------------------------

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return $this->errorResponse('Unauthenticated', null, 401);
        }

        return $this->successResponse($user->load(['roles', 'permissions', 'tenant']), 'Profile retrieved');
    }

    // -------------------------------------------------------------------------
    // PUT /api/users/me  – update own profile
    // -------------------------------------------------------------------------

    public function updateProfile(UpdateUserRequest $request): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = $request->user();

        try {
            $user = $this->userService->updateProfile($authUser->id, $request->validated());

            return $this->successResponse($user, 'Profile updated');
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to update profile', $e->getMessage(), 500);
        }
    }
}
