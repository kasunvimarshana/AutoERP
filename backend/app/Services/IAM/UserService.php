<?php

declare(strict_types=1);

namespace App\Services\IAM;

use App\Repositories\IAM\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Get all users with pagination
     */
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getAllWithRoles($perPage);
    }

    /**
     * Get users by tenant
     */
    public function getUsersByTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getUsersByTenant($tenantId, $perPage);
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Create new user
     */
    public function createUser(array $data): User
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Extract roles if provided
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        // Set tenant ID from current context if not provided
        if (!isset($data['tenant_id']) && app()->bound('current_tenant')) {
            $data['tenant_id'] = app('current_tenant')?->id;
        }

        return $this->userRepository->createWithRoles($data, $roles);
    }

    /**
     * Update user
     */
    public function updateUser(int $id, array $data): User
    {
        // Hash password if provided
        if (isset($data['password'])) {
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }
        }

        // Extract roles if provided
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        return $this->userRepository->updateWithRoles($id, $data, $roles);
    }

    /**
     * Delete user
     */
    public function deleteUser(int $id): bool
    {
        return $this->userRepository->delete($id);
    }

    /**
     * Assign roles to user
     */
    public function assignRoles(int $userId, array $roleNames): User
    {
        $user = $this->userRepository->find($userId);
        $user->syncRoles($roleNames);
        
        return $user->load('roles');
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions(int $userId): array
    {
        $user = $this->userRepository->find($userId);
        
        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    /**
     * Check if user has permission
     */
    public function userHasPermission(int $userId, string $permission): bool
    {
        $user = $this->userRepository->find($userId);
        
        return $user->hasPermissionTo($permission);
    }
}
