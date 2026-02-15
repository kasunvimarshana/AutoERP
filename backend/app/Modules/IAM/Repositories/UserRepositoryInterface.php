<?php

declare(strict_types=1);

namespace App\Modules\IAM\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all users for the current tenant with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return User|null
     */
    public function find(int $id): ?User;

    /**
     * Find a user by UUID.
     *
     * @param string $uuid
     * @return User|null
     */
    public function findByUuid(string $uuid): ?User;

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data): User;

    /**
     * Update a user.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function update(User $user, array $data): User;

    /**
     * Delete a user (soft delete).
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool;

    /**
     * Restore a soft-deleted user.
     *
     * @param int $id
     * @return bool
     */
    public function restore(int $id): bool;

    /**
     * Search users by criteria.
     *
     * @param array $criteria
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get users by role.
     *
     * @param string $roleName
     * @return Collection
     */
    public function getByRole(string $roleName): Collection;

    /**
     * Get users by organization.
     *
     * @param int $organizationId
     * @return Collection
     */
    public function getByOrganization(int $organizationId): Collection;

    /**
     * Get users by branch.
     *
     * @param int $branchId
     * @return Collection
     */
    public function getByBranch(int $branchId): Collection;

    /**
     * Get active users.
     *
     * @return Collection
     */
    public function getActiveUsers(): Collection;

    /**
     * Assign roles to user.
     *
     * @param User $user
     * @param array $roleNames
     * @return User
     */
    public function assignRoles(User $user, array $roleNames): User;

    /**
     * Sync roles for user.
     *
     * @param User $user
     * @param array $roleNames
     * @return User
     */
    public function syncRoles(User $user, array $roleNames): User;

    /**
     * Assign permissions to user.
     *
     * @param User $user
     * @param array $permissionNames
     * @return User
     */
    public function assignPermissions(User $user, array $permissionNames): User;
}
