<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\IAM\Entities\Role;

/**
 * Role Repository Interface
 *
 * Defines the contract for role data access operations.
 * Follows Repository Pattern for clean architecture.
 */
interface RoleRepositoryInterface
{
    /**
     * Find role by ID.
     */
    public function findById(int $id): ?Role;

    /**
     * Find role by slug.
     */
    public function findBySlug(string $slug): ?Role;

    /**
     * Get all roles.
     */
    public function all(): Collection;

    /**
     * Get paginated roles.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new role.
     */
    public function create(array $data): Role;

    /**
     * Update a role.
     */
    public function update(Role $role, array $data): Role;

    /**
     * Delete a role.
     */
    public function delete(Role $role): bool;

    /**
     * Get active roles.
     */
    public function findActive(): Collection;

    /**
     * Get system roles.
     */
    public function findSystem(): Collection;

    /**
     * Get custom (non-system) roles.
     */
    public function findCustom(): Collection;

    /**
     * Get top-level roles (no parent).
     */
    public function findTopLevel(): Collection;

    /**
     * Get roles by level.
     */
    public function findByLevel(int $level): Collection;

    /**
     * Get roles by parent ID.
     */
    public function findByParent(int $parentId): Collection;

    /**
     * Search roles by name or slug.
     */
    public function search(string $query): Collection;

    /**
     * Assign permissions to role.
     */
    public function assignPermissions(Role $role, array $permissionIds): void;

    /**
     * Sync permissions to role.
     */
    public function syncPermissions(Role $role, array $permissionIds): void;

    /**
     * Remove permissions from role.
     */
    public function removePermissions(Role $role, array $permissionIds): void;

    /**
     * Get all permissions for a role including inherited.
     */
    public function getAllPermissions(Role $role): array;
}
