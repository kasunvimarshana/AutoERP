<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Return a paginated, filtered, and sorted list of users.
     */
    public function index(Request $request): UserCollection|JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'is_active',
                'department',
                'role',
                'sort_by',
                'sort_direction',
                'per_page',
            ]);

            $users = $this->userService->getAllUsers($filters);

            return new UserCollection($users);
        } catch (Throwable $e) {
            Log::error('Failed to fetch users', [
                'error'   => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve users.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return a single user by ID.
     */
    public function show(int $id): UserResource|JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            if ($user === null) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            return new UserResource($user);
        } catch (Throwable $e) {
            Log::error('Failed to fetch user', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to retrieve user.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new user.
     */
    public function store(StoreUserRequest $request): UserResource|JsonResponse
    {
        try {
            $user = $this->userService->createUser($request->validated());

            return (new UserResource($user))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Failed to create user.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing user.
     */
    public function update(UpdateUserRequest $request, int $id): UserResource|JsonResponse
    {
        try {
            $user = $this->userService->updateUser($id, $request->validated());

            if ($user === null) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            return new UserResource($user);
        } catch (Throwable $e) {
            Log::error('Failed to update user', [
                'id'    => $id,
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Failed to update user.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Soft-delete a user.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->userService->deleteUser($id);

            if (! $deleted) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            return response()->json(['message' => 'User deleted successfully.'], 200);
        } catch (Throwable $e) {
            Log::error('Failed to delete user', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to delete user.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return the profile of the currently authenticated user (from JWT).
     */
    public function getUserProfile(Request $request): UserResource|JsonResponse
    {
        try {
            $keycloakId = $request->attributes->get('user_id');

            if ($keycloakId === null) {
                return response()->json(['message' => 'Unauthorized: Missing token subject.'], 401);
            }

            $user = $this->userService->getUserByKeycloakId($keycloakId);

            if ($user === null) {
                return response()->json(['message' => 'User profile not found.'], 404);
            }

            return new UserResource($user);
        } catch (Throwable $e) {
            Log::error('Failed to fetch user profile', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to retrieve user profile.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the profile of the currently authenticated user.
     */
    public function updateMyProfile(UpdateUserRequest $request): UserResource|JsonResponse
    {
        try {
            $keycloakId = $request->attributes->get('user_id');

            if ($keycloakId === null) {
                return response()->json(['message' => 'Unauthorized: Missing token subject.'], 401);
            }

            $user = $this->userService->getUserByKeycloakId($keycloakId);

            if ($user === null) {
                return response()->json(['message' => 'User profile not found.'], 404);
            }

            // Disallow role or activation status self-modification
            $data = $request->except(['roles', 'is_active', 'keycloak_id']);

            $updated = $this->userService->updateUser($user->id, $data);

            return new UserResource($updated);
        } catch (Throwable $e) {
            Log::error('Failed to update user profile', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to update user profile.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, int $id): UserResource|JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'max:100'],
        ]);

        try {
            $user = $this->userService->assignRole($id, $request->input('role'));

            if ($user === null) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            return new UserResource($user);
        } catch (Throwable $e) {
            Log::error('Failed to assign role', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to assign role.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke a role from a user.
     */
    public function revokeRole(Request $request, int $id): UserResource|JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'max:100'],
        ]);

        try {
            $user = $this->userService->revokeRole($id, $request->input('role'));

            if ($user === null) {
                return response()->json(['message' => 'User not found.'], 404);
            }

            return new UserResource($user);
        } catch (Throwable $e) {
            Log::error('Failed to revoke role', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to revoke role.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
