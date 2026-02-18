<?php

declare(strict_types=1);

namespace Modules\IAM\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\IAM\Repositories\Contracts\UserRepositoryInterface;

/**
 * User Repository
 *
 * Implements data access operations for users.
 * Uses native Laravel Eloquent - no external packages.
 */
class UserRepository implements UserRepositoryInterface
{
    public function __construct(
        protected User $model
    ) {}

    /**
     * Find user by ID.
     */
    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get all users.
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get paginated users.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        return $this->model->create($data);
    }

    /**
     * Update a user.
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    /**
     * Delete a user.
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Get active users.
     */
    public function findActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get users by tenant.
     */
    public function findByTenant(int $tenantId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    /**
     * Get users by role.
     */
    public function findByRole(int $roleId): Collection
    {
        return $this->model->whereHas('roles', function ($query) use ($roleId) {
            $query->where('role_id', $roleId);
        })->get();
    }

    /**
     * Get users by group.
     */
    public function findByGroup(int $groupId): Collection
    {
        return $this->model->whereHas('groups', function ($query) use ($groupId) {
            $query->where('group_id', $groupId);
        })->get();
    }

    /**
     * Search users by name or email.
     */
    public function search(string $query): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->get();
    }

    /**
     * Assign roles to user.
     */
    public function assignRoles(User $user, array $roleIds): void
    {
        if (method_exists($user, 'roles')) {
            $user->roles()->syncWithoutDetaching($roleIds);
        }
    }

    /**
     * Sync roles to user.
     */
    public function syncRoles(User $user, array $roleIds): void
    {
        if (method_exists($user, 'roles')) {
            $user->roles()->sync($roleIds);
        }
    }

    /**
     * Remove roles from user.
     */
    public function removeRoles(User $user, array $roleIds): void
    {
        if (method_exists($user, 'roles')) {
            $user->roles()->detach($roleIds);
        }
    }

    /**
     * Assign permissions to user.
     */
    public function assignPermissions(User $user, array $permissionIds): void
    {
        if (method_exists($user, 'assignPermissions')) {
            $user->assignPermissions($permissionIds);
        }
    }

    /**
     * Sync permissions to user.
     */
    public function syncPermissions(User $user, array $permissionIds): void
    {
        if (method_exists($user, 'syncPermissions')) {
            $user->syncPermissions($permissionIds);
        }
    }

    /**
     * Remove permissions from user.
     */
    public function removePermissions(User $user, array $permissionIds): void
    {
        if (method_exists($user, 'removePermissions')) {
            $user->removePermissions($permissionIds);
        }
    }

    /**
     * Get all permissions for a user including inherited from roles.
     */
    public function getAllPermissions(User $user): array
    {
        if (method_exists($user, 'getAllPermissions')) {
            return $user->getAllPermissions();
        }

        return $user->permissions ?? [];
    }

    /**
     * Check if user has permission.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }

        return in_array($permission, $this->getAllPermissions($user));
    }
}
