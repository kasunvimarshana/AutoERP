<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\IAM\Entities\Permission;
use Modules\IAM\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionRepository implements PermissionRepositoryInterface
{
    public function __construct(
        protected Permission $model
    ) {}

    /**
     * Find permission by ID.
     */
    public function findById(int $id): ?Permission
    {
        return $this->model->find($id);
    }

    /**
     * Find permission by slug.
     */
    public function findBySlug(string $slug): ?Permission
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Get all permissions.
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get paginated permissions.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Create a new permission.
     */
    public function create(array $data): Permission
    {
        return $this->model->create($data);
    }

    /**
     * Update a permission.
     */
    public function update(Permission $permission, array $data): Permission
    {
        $permission->update($data);

        return $permission->fresh();
    }

    /**
     * Delete a permission.
     */
    public function delete(Permission $permission): bool
    {
        return $permission->delete();
    }

    /**
     * Get active permissions.
     */
    public function findActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Get permissions by module.
     */
    public function findByModule(string $module): Collection
    {
        return $this->model->forModule($module)->get();
    }

    /**
     * Get permissions by resource.
     */
    public function findByResource(string $resource): Collection
    {
        return $this->model->forResource($resource)->get();
    }

    /**
     * Search permissions by name or slug.
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
     * Sync permissions to a role.
     */
    public function syncToRole(int $roleId, array $permissionIds): void
    {
        $role = app(config('auth.models.role', 'App\Models\Role'))->findOrFail($roleId);
        $role->permissions()->sync($permissionIds);
    }

    /**
     * Sync permissions to a user.
     */
    public function syncToUser(int $userId, array $permissionIds, string $type = 'grant'): void
    {
        $user = app(config('auth.providers.users.model', 'App\Models\User'))->findOrFail($userId);

        // Detach all existing permissions
        $user->permissions()->detach();

        // Attach new permissions with type
        foreach ($permissionIds as $permissionId) {
            $user->permissions()->attach($permissionId, ['type' => $type]);
        }
    }
}
