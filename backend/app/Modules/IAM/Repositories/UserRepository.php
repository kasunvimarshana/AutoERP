<?php

declare(strict_types=1);

namespace App\Modules\IAM\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get all users for the current tenant with pagination.
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::with(['tenant', 'organization', 'branch', 'roles'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a user by ID.
     */
    public function find(int $id): ?User
    {
        return User::with(['tenant', 'organization', 'branch', 'roles', 'permissions'])
            ->find($id);
    }

    /**
     * Find a user by UUID.
     */
    public function findByUuid(string $uuid): ?User
    {
        return User::with(['tenant', 'organization', 'branch', 'roles', 'permissions'])
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Create a new user.
     */
    public function create(array $data): User
    {
        // Set tenant context if not provided
        if (!isset($data['tenant_id'])) {
            $data['tenant_id'] = config('app.current_tenant_id') ?? auth()->user()?->tenant_id;
        }

        // Set creator
        $data['created_by'] = auth()->id();

        return User::create($data);
    }

    /**
     * Update a user.
     */
    public function update(User $user, array $data): User
    {
        $data['updated_by'] = auth()->id();
        $user->update($data);
        return $user->fresh(['tenant', 'organization', 'branch', 'roles', 'permissions']);
    }

    /**
     * Delete a user (soft delete).
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(int $id): bool
    {
        $user = User::withTrashed()->find($id);
        return $user ? $user->restore() : false;
    }

    /**
     * Search users by criteria.
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with(['tenant', 'organization', 'branch', 'roles']);

        if (isset($criteria['name'])) {
            $query->where('name', 'like', '%' . $criteria['name'] . '%');
        }

        if (isset($criteria['email'])) {
            $query->where('email', 'like', '%' . $criteria['email'] . '%');
        }

        if (isset($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (isset($criteria['organization_id'])) {
            $query->where('organization_id', $criteria['organization_id']);
        }

        if (isset($criteria['branch_id'])) {
            $query->where('branch_id', $criteria['branch_id']);
        }

        if (isset($criteria['role'])) {
            $query->role($criteria['role']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get users by role.
     */
    public function getByRole(string $roleName): Collection
    {
        return User::role($roleName)
            ->with(['tenant', 'organization', 'branch'])
            ->get();
    }

    /**
     * Get users by organization.
     */
    public function getByOrganization(int $organizationId): Collection
    {
        return User::where('organization_id', $organizationId)
            ->with(['tenant', 'branch', 'roles'])
            ->get();
    }

    /**
     * Get users by branch.
     */
    public function getByBranch(int $branchId): Collection
    {
        return User::where('branch_id', $branchId)
            ->with(['tenant', 'organization', 'roles'])
            ->get();
    }

    /**
     * Get active users.
     */
    public function getActiveUsers(): Collection
    {
        return User::where('status', 'active')
            ->with(['tenant', 'organization', 'branch', 'roles'])
            ->get();
    }

    /**
     * Assign roles to user.
     */
    public function assignRoles(User $user, array $roleNames): User
    {
        $user->assignRole($roleNames);
        return $user->fresh(['roles']);
    }

    /**
     * Sync roles for user.
     */
    public function syncRoles(User $user, array $roleNames): User
    {
        $user->syncRoles($roleNames);
        return $user->fresh(['roles']);
    }

    /**
     * Assign permissions to user.
     */
    public function assignPermissions(User $user, array $permissionNames): User
    {
        $user->givePermissionTo($permissionNames);
        return $user->fresh(['permissions']);
    }
}
