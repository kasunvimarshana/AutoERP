<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\AuthServiceInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * User Controller
 *
 * Handles CRUD operations for users within a tenant context.
 * Thin controller - delegates business logic to services/repositories.
 */
class UserController extends Controller
{
    public function __construct(
        protected readonly UserRepositoryInterface $userRepository,
        protected readonly AuthServiceInterface $authService
    ) {}

    /**
     * List users for the authenticated tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id');

        $filters = $request->only([
            'search', 'is_active', 'sort_by', 'sort_dir',
            'per_page', 'page',
        ]);

        $users = $this->userRepository->findByTenant($tenantId, $filters);

        if ($users instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }

    /**
     * Get a specific user.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->findById($id, ['roles.permissions']);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update a user.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userRepository->update($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->userRepository->delete($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $request->validate(['role' => ['required', 'string', 'max:50']]);

        $this->authService->assignRole($id, $request->input('role'));

        return response()->json([
            'success' => true,
            'message' => 'Role assigned successfully.',
        ]);
    }
}
