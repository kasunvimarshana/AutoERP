<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * User Repository Interface
 *
 * Defines the contract for user data access operations.
 * Follows Repository Pattern for clean architecture.
 */
interface UserRepositoryInterface
{
    /**
     * Find user by ID.
     */
    public function findById(int $id): ?User;

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Get all users.
     */
    public function all(): Collection;

    /**
     * Get paginated users.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new user.
     */
    public function create(array $data): User;

    /**
     * Update a user.
     */
    public function update(User $user, array $data): User;

    /**
     * Delete a user.
     */
    public function delete(User $user): bool;

    /**
     * Get active users.
     */
    public function findActive(): Collection;

    /**
     * Get users by tenant.
     */
    public function findByTenant(int $tenantId): Collection;

    /**
     * Get users by role.
     */
    public function findByRole(int $roleId): Collection;

    /**
     * Get users by group.
     */
    public function findByGroup(int $groupId): Collection;

    /**
     * Search users by name or email.
     */
    public function search(string $query): Collection;

    /**
     * Assign roles to user.
     */
    public function assignRoles(User $user, array $roleIds): void;

    /**
     * Sync roles to user.
     */
    public function syncRoles(User $user, array $roleIds): void;

    /**
     * Remove roles from user.
     */
    public function removeRoles(User $user, array $roleIds): void;

    /**
     * Assign permissions to user.
     */
    public function assignPermissions(User $user, array $permissionIds): void;

    /**
     * Sync permissions to user.
     */
    public function syncPermissions(User $user, array $permissionIds): void;

    /**
     * Remove permissions from user.
     */
    public function removePermissions(User $user, array $permissionIds): void;

    /**
     * Get all permissions for a user including inherited from roles.
     */
    public function getAllPermissions(User $user): array;

    /**
     * Check if user has permission.
     */
    public function hasPermission(User $user, string $permission): bool;
}
