<?php

namespace App\Modules\IAM\Repositories;

use App\Models\User;
use App\Modules\IAM\Models\Role;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * User Repository
 *
 * Handles data access for users.
 */
class UserRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return User::class;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get users by tenant
     */
    public function getByTenant(int $tenantId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(int $roleId): Collection
    {
        return $this->model
            ->whereHas('roles', function ($query) use ($roleId) {
                $query->where('roles.id', $roleId);
            })
            ->get();
    }

    /**
     * Assign role to user
     */
    public function assignRole(User $user, Role $role): void
    {
        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Remove role from user
     */
    public function removeRole(User $user, Role $role): void
    {
        $user->roles()->detach($role->id);
    }

    /**
     * Sync user roles
     */
    public function syncRoles(User $user, array $roleIds): void
    {
        $user->roles()->sync($roleIds);
    }

    /**
     * Search users
     */
    public function search(string $search): Collection
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->get();
    }
}
