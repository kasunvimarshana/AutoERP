<?php

declare(strict_types=1);

namespace Modules\IAM\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\IAM\Repositories\Contracts\UserRepositoryInterface;

/**
 * User Service
 *
 * Handles business logic for user management.
 * Orchestrates operations between repositories and domain logic.
 */
class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get paginated users.
     */
    public function getPaginatedUsers(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    /**
     * Get all active users.
     */
    public function getActiveUsers(): Collection
    {
        return $this->userRepository->findActive();
    }

    /**
     * Find user by ID.
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Find user by email.
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Set default active status
            $data['is_active'] = $data['is_active'] ?? true;

            // Create user
            $user = $this->userRepository->create($data);

            // Assign roles if provided
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->userRepository->syncRoles($user, $data['roles']);
            }

            // Assign permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $this->userRepository->syncPermissions($user, $data['permissions']);
            }

            return $user;
        });
    }

    /**
     * Update user.
     */
    public function updateUser(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->userRepository->findById($id);

            if (! $user) {
                throw new \Exception("User not found: {$id}");
            }

            // Hash password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Update user
            $user = $this->userRepository->update($user, $data);

            // Sync roles if provided
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->userRepository->syncRoles($user, $data['roles']);
            }

            // Sync permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $this->userRepository->syncPermissions($user, $data['permissions']);
            }

            return $user;
        });
    }

    /**
     * Delete user.
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw new \Exception("User not found: {$id}");
        }

        return $this->userRepository->delete($user);
    }

    /**
     * Activate user.
     */
    public function activateUser(int $id): User
    {
        return $this->updateUser($id, ['is_active' => true]);
    }

    /**
     * Deactivate user.
     */
    public function deactivateUser(int $id): User
    {
        return $this->updateUser($id, ['is_active' => false]);
    }

    /**
     * Assign roles to user.
     */
    public function assignRoles(int $userId, array $roleIds): void
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new \Exception("User not found: {$userId}");
        }

        $this->userRepository->assignRoles($user, $roleIds);
    }

    /**
     * Remove roles from user.
     */
    public function removeRoles(int $userId, array $roleIds): void
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new \Exception("User not found: {$userId}");
        }

        $this->userRepository->removeRoles($user, $roleIds);
    }

    /**
     * Assign permissions to user.
     */
    public function assignPermissions(int $userId, array $permissionIds): void
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new \Exception("User not found: {$userId}");
        }

        $this->userRepository->assignPermissions($user, $permissionIds);
    }

    /**
     * Remove permissions from user.
     */
    public function removePermissions(int $userId, array $permissionIds): void
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new \Exception("User not found: {$userId}");
        }

        $this->userRepository->removePermissions($user, $permissionIds);
    }

    /**
     * Get all permissions for a user.
     */
    public function getUserPermissions(int $userId): array
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new \Exception("User not found: {$userId}");
        }

        return $this->userRepository->getAllPermissions($user);
    }

    /**
     * Check if user has permission.
     */
    public function userHasPermission(int $userId, string $permission): bool
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            return false;
        }

        return $this->userRepository->hasPermission($user, $permission);
    }

    /**
     * Search users.
     */
    public function searchUsers(string $query): Collection
    {
        return $this->userRepository->search($query);
    }

    /**
     * Get users by tenant.
     */
    public function getUsersByTenant(int $tenantId): Collection
    {
        return $this->userRepository->findByTenant($tenantId);
    }

    /**
     * Get users by role.
     */
    public function getUsersByRole(int $roleId): Collection
    {
        return $this->userRepository->findByRole($roleId);
    }

    /**
     * Get users by group.
     */
    public function getUsersByGroup(int $groupId): Collection
    {
        return $this->userRepository->findByGroup($groupId);
    }
}
