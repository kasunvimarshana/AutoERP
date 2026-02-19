<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Auth\Models\User;
use Modules\Core\Repositories\BaseRepository;

/**
 * User Repository
 *
 * Handles user data access operations
 */
class UserRepository extends BaseRepository
{
    /**
     * Make a new User model instance
     */
    protected function makeModel(): Model
    {
        return new User;
    }

    /**
     * Find user by ID with tenant scope
     */
    public function findByIdWithTenant(string $id, ?string $tenantId): ?User
    {
        $query = $this->model->where('id', $id);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email, string $tenantId): ?User
    {
        return $this->model
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Find user by email for authentication
     */
    public function findByEmailForAuth(string $email, string $tenantId): ?User
    {
        return $this->model
            ->with(['roles.permissions', 'permissions'])
            ->where('email', $email)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Find users by organization
     */
    public function findByOrganization(string $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('organization_id', $organizationId)
            ->with(['roles', 'organization'])
            ->paginate($perPage);
    }

    /**
     * Find active users by tenant
     */
    public function findActiveByTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['roles', 'organization'])
            ->paginate($perPage);
    }

    /**
     * Find users by role
     */
    public function findByRole(string $roleId, string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->whereHas('roles', function ($query) use ($roleId) {
                $query->where('role_id', $roleId);
            })
            ->with(['roles', 'organization'])
            ->paginate($perPage);
    }

    /**
     * Search users by name or email
     */
    public function searchUsers(string $term, string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->with(['roles', 'organization'])
            ->paginate($perPage);
    }

    /**
     * Activate user
     */
    public function activate(string $id): bool
    {
        return $this->update($id, ['is_active' => true]);
    }

    /**
     * Deactivate user
     */
    public function deactivate(string $id): bool
    {
        return $this->update($id, ['is_active' => false]);
    }

    /**
     * Attach role to user
     */
    public function attachRole(string $userId, string $roleId): void
    {
        $user = $this->findOrFail($userId);
        if (! $user->roles()->where('role_id', $roleId)->exists()) {
            $user->roles()->attach($roleId);
        }
    }

    /**
     * Detach role from user
     */
    public function detachRole(string $userId, string $roleId): void
    {
        $user = $this->findOrFail($userId);
        $user->roles()->detach($roleId);
    }

    /**
     * Sync user roles
     */
    public function syncRoles(string $userId, array $roleIds): void
    {
        $user = $this->findOrFail($userId);
        $user->roles()->sync($roleIds);
    }

    /**
     * Attach permission to user
     */
    public function attachPermission(string $userId, string $permissionId): void
    {
        $user = $this->findOrFail($userId);
        if (! $user->permissions()->where('permission_id', $permissionId)->exists()) {
            $user->permissions()->attach($permissionId);
        }
    }

    /**
     * Detach permission from user
     */
    public function detachPermission(string $userId, string $permissionId): void
    {
        $user = $this->findOrFail($userId);
        $user->permissions()->detach($permissionId);
    }

    /**
     * Sync user permissions
     */
    public function syncPermissions(string $userId, array $permissionIds): void
    {
        $user = $this->findOrFail($userId);
        $user->permissions()->sync($permissionIds);
    }

    /**
     * Get user with roles and permissions
     */
    public function findWithRolesAndPermissions(string $id): ?User
    {
        return $this->model
            ->with(['roles.permissions', 'permissions'])
            ->find($id);
    }

    /**
     * Check if email exists for tenant
     */
    public function emailExists(string $email, string $tenantId, ?string $excludeUserId = null): bool
    {
        $query = $this->model
            ->where('email', $email)
            ->where('tenant_id', $tenantId);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->exists();
    }

    /**
     * Get user device count
     */
    public function getDeviceCount(string $userId): int
    {
        $user = $this->findOrFail($userId);

        return $user->devices()->count();
    }

    /**
     * Get user devices
     */
    public function getDevices(string $userId): Collection
    {
        $user = $this->findOrFail($userId);

        return $user->devices()->orderBy('last_used_at', 'desc')->get();
    }

    /**
     * Update user metadata
     */
    public function updateMetadata(string $userId, array $metadata): bool
    {
        $user = $this->findOrFail($userId);
        $currentMetadata = $user->metadata ?? [];

        return $user->update(['metadata' => array_merge($currentMetadata, $metadata)]);
    }

    /**
     * Verify user email
     */
    public function verifyEmail(string $userId): bool
    {
        return $this->update($userId, ['email_verified_at' => now()]);
    }

    /**
     * Count active users by tenant
     */
    public function countActiveByTenant(string $tenantId): int
    {
        return $this->count([
            'tenant_id' => $tenantId,
            'is_active' => true,
        ]);
    }

    /**
     * Count users by organization
     */
    public function countByOrganization(string $organizationId): int
    {
        return $this->count(['organization_id' => $organizationId]);
    }

    /**
     * Find users with filters and pagination.
     */
    public function findWithFilters(array $filters, ?string $tenantId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['roles', 'organization']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (isset($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest()->paginate($perPage);
    }
}
