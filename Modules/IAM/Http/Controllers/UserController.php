<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\IAM\Http\Requests\CreateUserRequest;
use Modules\IAM\Http\Requests\UpdateUserRequest;
use Modules\IAM\Http\Resources\UserResource;
use Modules\IAM\Services\UserService;

/**
 * User Controller
 *
 * Handles HTTP requests for user management.
 * Delegates business logic to UserService.
 */
class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $users = $this->userService->getPaginatedUsers($perPage);

        return response()->json(UserResource::collection($users));
    }

    /**
     * Store a newly created user.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return response()->json(
                new UserResource($user),
                201
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json(new UserResource($user));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $user = $this->userService->updateUser($user->id, $request->validated());

            return response()->json(new UserResource($user));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        try {
            $this->userService->deleteUser($user->id);

            return response()->noContent();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Activate user.
     */
    public function activate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        try {
            $user = $this->userService->activateUser($user->id);

            return response()->json(new UserResource($user));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to activate user',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        try {
            $user = $this->userService->deactivateUser($user->id);

            return response()->json(new UserResource($user));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deactivate user',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign roles to user.
     */
    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['required', 'integer', 'exists:roles,id'],
        ]);

        try {
            $this->userService->assignRoles($user->id, $request->input('roles'));

            return response()->json([
                'message' => 'Roles assigned successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign roles',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Assign permissions to user.
     */
    public function assignPermissions(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['required', 'integer', 'exists:permissions,id'],
        ]);

        try {
            $this->userService->assignPermissions($user->id, $request->input('permissions'));

            return response()->json([
                'message' => 'Permissions assigned successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign permissions',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user permissions.
     */
    public function permissions(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        try {
            $permissions = $this->userService->getUserPermissions($user->id);

            return response()->json([
                'permissions' => $permissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get permissions',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Search users.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:2'],
        ]);

        $users = $this->userService->searchUsers($request->input('query'));

        return response()->json(UserResource::collection($users));
    }
}
