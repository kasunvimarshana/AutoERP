<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Model
    {
        return $this->newQuery()->where('email', $email)->first();
    }

    public function getByTenant(int|string $tenantId): Collection
    {
        return $this->newQuery()->where('tenant_id', $tenantId)->get();
    }

    public function assignRoles(int|string $userId, array $roleIds): void
    {
        $user = $this->newQuery()->findOrFail($userId);
        $user->roles()->attach($roleIds);
    }

    public function syncRoles(int|string $userId, array $roleIds): void
    {
        $user = $this->newQuery()->findOrFail($userId);
        $user->roles()->sync($roleIds);
    }

    public function revokeRoles(int|string $userId, array $roleIds): void
    {
        $user = $this->newQuery()->findOrFail($userId);
        $user->roles()->detach($roleIds);
    }

    public function assignPermissions(int|string $userId, array $permissionIds): void
    {
        $user = $this->newQuery()->findOrFail($userId);
        $user->permissions()->attach($permissionIds);
    }

    public function syncPermissions(int|string $userId, array $permissionIds): void
    {
        $user = $this->newQuery()->findOrFail($userId);
        $user->permissions()->sync($permissionIds);
    }

    public function findByRole(string $roleName): Collection
    {
        return $this->newQuery()
            ->whereHas('roles', fn ($q) => $q->where('name', $roleName))
            ->get();
    }

    public function getActiveUsers(int|string $tenantId): Collection
    {
        return $this->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
    }

    public function deleteByTenant(int|string $tenantId): int
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->delete();
    }
}
