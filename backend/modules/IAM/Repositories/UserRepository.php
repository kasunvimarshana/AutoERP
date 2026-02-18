<?php

namespace Modules\IAM\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\IAM\Models\User;

class UserRepository extends BaseRepository
{
    protected function model(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findBy('email', $email);
    }

    public function findActiveByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)
            ->where('is_active', true)
            ->first();
    }

    public function getAllForTenant(int $tenantId): Collection
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getActiveUsers(int $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();
    }

    public function updateLastLogin(User $user, string $ipAddress): bool
    {
        return $this->update($user, [
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    public function searchUsers(int $tenantId, string $query, int $perPage = 15)
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->paginate($perPage);
    }

    public function getUsersWithRoles(int $tenantId, int $perPage = 15)
    {
        return $this->model
            ->with(['roles', 'permissions'])
            ->where('tenant_id', $tenantId)
            ->paginate($perPage);
    }

    public function countActiveUsers(int $tenantId): int
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
    }
}
