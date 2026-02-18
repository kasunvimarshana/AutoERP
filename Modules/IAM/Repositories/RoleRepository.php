<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\IAM\Entities\Role;
use Modules\IAM\Repositories\Contracts\RoleRepositoryInterface;

/**
 * Role Repository
 *
 * Implements data access operations for roles.
 * Uses native Laravel Eloquent - no external packages.
 */
class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        protected Role $model
    ) {}

    /**
     * Find role by ID.
     */
    public function findById(int $id): ?Role
    {
        return $this->model->find($id);
    }

    /**
     * Find role by slug.
     */
    public function findBySlug(string $slug): ?Role
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Get all roles.
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get paginated roles.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->with(['parent', 'rolePermissions'])->paginate($perPage);
    }

    /**
     * Create a new role.
     */
    public function create(array $data): Role
    {
        return $this->model->create($data);
    }

    /**
     * Update a role.
     */
    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role->fresh();
    }

    /**
     * Delete a role.
     */
    public function delete(Role $role): bool
    {
        return $role->delete();
    }

    /**
     * Get active roles.
     */
    public function findActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get system roles.
     */
    public function findSystem(): Collection
    {
        return $this->model->system()->get();
    }

    /**
     * Get custom (non-system) roles.
     */
    public function findCustom(): Collection
    {
        return $this->model->custom()->get();
    }

    /**
     * Get top-level roles (no parent).
     */
    public function findTopLevel(): Collection
    {
        return $this->model->topLevel()->get();
    }

    /**
     * Get roles by level.
     */
    public function findByLevel(int $level): Collection
    {
        return $this->model->level($level)->get();
    }

    /**
     * Get roles by parent ID.
     */
    public function findByParent(int $parentId): Collection
    {
        return $this->model->where('parent_id', $parentId)->get();
    }

    /**
     * Search roles by name or slug.
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('slug', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->get();
    }

    /**
     * Assign permissions to role.
     */
    public function assignPermissions(Role $role, array $permissionIds): void
    {
        $role->rolePermissions()->syncWithoutDetaching($permissionIds);
    }

    /**
     * Sync permissions to role.
     */
    public function syncPermissions(Role $role, array $permissionIds): void
    {
        $role->rolePermissions()->sync($permissionIds);
    }

    /**
     * Remove permissions from role.
     */
    public function removePermissions(Role $role, array $permissionIds): void
    {
        $role->rolePermissions()->detach($permissionIds);
    }

    /**
     * Get all permissions for a role including inherited.
     */
    public function getAllPermissions(Role $role): array
    {
        return $role->getAllPermissions();
    }
}
