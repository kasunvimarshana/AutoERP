<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\IAM\Entities\Permission;

interface PermissionRepositoryInterface
{
    /**
     * Find permission by ID.
     */
    public function findById(int $id): ?Permission;

    /**
     * Find permission by slug.
     */
    public function findBySlug(string $slug): ?Permission;

    /**
     * Get all permissions.
     */
    public function all(): Collection;

    /**
     * Get paginated permissions.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new permission.
     */
    public function create(array $data): Permission;

    /**
     * Update a permission.
     */
    public function update(Permission $permission, array $data): Permission;

    /**
     * Delete a permission.
     */
    public function delete(Permission $permission): bool;

    /**
     * Get active permissions.
     */
    public function findActive(): Collection;

    /**
     * Get permissions by module.
     */
    public function findByModule(string $module): Collection;

    /**
     * Get permissions by resource.
     */
    public function findByResource(string $resource): Collection;

    /**
     * Search permissions by name or slug.
     */
    public function search(string $query): Collection;

    /**
     * Sync permissions to a role.
     */
    public function syncToRole(int $roleId, array $permissionIds): void;

    /**
     * Sync permissions to a user.
     */
    public function syncToUser(int $userId, array $permissionIds, string $type = 'grant'): void;
}
