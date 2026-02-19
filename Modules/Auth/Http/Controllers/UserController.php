<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Http\Requests\StoreUserRequest;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\UserRepository;
use Modules\Auth\Services\UserService;
use Modules\Tenant\Services\TenantContext;

/**
 * UserController
 *
 * Handles CRUD operations for users
 */
class UserController extends ApiController
{
    public function __construct(
        protected UserService $userService,
        protected UserRepository $userRepository,
        protected TenantContext $tenantContext
    ) {}

    /**
     * List all users
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'search' => request()->input('search'),
            'organization_id' => request()->input('organization_id'),
            'is_active' => request()->input('is_active'),
        ];

        $filters = array_filter($filters, fn($value) => $value !== null);
        $perPage = request()->input('per_page', 15);
        
        $tenantId = $this->tenantContext->hasTenant() 
            ? $this->tenantContext->getCurrentTenantId() 
            : null;

        $users = $this->userRepository->findWithFilters($filters, $tenantId, $perPage);

        return $this->paginated($users, UserResource::class);
    }

    /**
     * Create a new user
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $organizationId = $request->input('organization_id');

        $user = $this->userService->createUser($data, $organizationId);

        return $this->created(
            new UserResource($user->load(['roles', 'permissions', 'organization'])),
            'User created successfully'
        );
    }

    /**
     * Show a specific user
     */
    public function show(string $id): JsonResponse
    {
        $tenantId = $this->tenantContext->hasTenant() 
            ? $this->tenantContext->getCurrentTenantId() 
            : null;

        $user = $this->userRepository->findByIdWithTenant($id, $tenantId);

        if (! $user) {
            return $this->notFound('User not found');
        }

        $this->authorize('view', $user);

        $user->load(['roles', 'permissions', 'organization', 'devices']);

        return $this->success(new UserResource($user));
    }

    /**
     * Update a user
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $tenantId = $this->tenantContext->hasTenant() 
            ? $this->tenantContext->getCurrentTenantId() 
            : null;

        $user = $this->userRepository->findByIdWithTenant($id, $tenantId);

        if (! $user) {
            return $this->notFound('User not found');
        }

        $this->authorize('update', $user);

        $data = $request->validated();
        $updatedUser = $this->userService->updateUser($id, $data);

        return $this->success(
            new UserResource($updatedUser->fresh(['roles', 'permissions', 'organization'])),
            'User updated successfully'
        );
    }

    /**
     * Delete a user
     */
    public function destroy(string $id): JsonResponse
    {
        $tenantId = $this->tenantContext->hasTenant() 
            ? $this->tenantContext->getCurrentTenantId() 
            : null;

        $user = $this->userRepository->findByIdWithTenant($id, $tenantId);

        if (! $user) {
            return $this->notFound('User not found');
        }

        $this->authorize('delete', $user);

        $this->userService->deleteUser($id);

        return $this->success(null, 'User deleted successfully');
    }
}
