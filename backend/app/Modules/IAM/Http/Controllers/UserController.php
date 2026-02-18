<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\IAM\DTOs\UserDTO;
use App\Modules\IAM\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * User API Controller
 *
 * Handles HTTP requests for user management.
 */
class UserController extends BaseController
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $tenantId = $request->input('tenant_id');

        if ($tenantId) {
            $users = $this->userService->getAllUsers($tenantId);

            return $this->successResponse($users, 'Users retrieved successfully');
        }

        $users = $this->userService->getPaginatedUsers($perPage);

        return $this->successResponse($users, 'Users retrieved successfully');
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'is_active' => 'boolean',
        ]);

        try {
            $dto = UserDTO::fromArray($validated);
            $user = $this->userService->createUser($dto);

            return $this->createdResponse($user, 'User created successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * Display the specified user
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            // Load relationships
            $user->load(['roles', 'tenant']);

            return $this->successResponse($user, 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('User not found');
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,'.$id,
            'password' => 'nullable|string|min:8',
            'is_active' => 'boolean',
        ]);

        try {
            $user = $this->userService->updateUser($id, $validated);

            return $this->successResponse($user, 'User updated successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        } catch (\Exception $e) {
            return $this->notFoundResponse('User not found');
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->deleteUser($id);

            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('User not found');
        }
    }

    /**
     * Assign roles to user
     */
    public function assignRoles(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        try {
            $user = $this->userService->assignRoles($id, $validated['role_ids']);

            return $this->successResponse($user, 'Roles assigned successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * Sync user roles
     */
    public function syncRoles(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        try {
            $user = $this->userService->syncRoles($id, $validated['role_ids']);

            return $this->successResponse($user, 'User roles synchronized successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 422);
        }
    }

    /**
     * Get user roles
     */
    public function getRoles(int $id): JsonResponse
    {
        try {
            $roles = $this->userService->getUserRoles($id);

            return $this->successResponse($roles, 'User roles retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('User not found');
        }
    }

    /**
     * Get user permissions
     */
    public function getPermissions(int $id): JsonResponse
    {
        try {
            $permissions = $this->userService->getUserPermissions($id);

            return $this->successResponse($permissions, 'User permissions retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('User not found');
        }
    }

    /**
     * Search users
     */
    public function search(Request $request): JsonResponse
    {
        $search = $request->input('q', '');
        $users = $this->userService->searchUsers($search);

        return $this->successResponse($users, 'Search results retrieved successfully');
    }

    /**
     * Get active users
     */
    public function active(): JsonResponse
    {
        $users = $this->userService->getActiveUsers();

        return $this->successResponse($users, 'Active users retrieved successfully');
    }
}
