<?php

declare(strict_types=1);

namespace App\Repositories\IAM;

use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Get users with their roles
     */
    public function getAllWithRoles(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with('roles')
            ->paginate($perPage);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model
            ->where('email', $email)
            ->first();
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $roleName): Collection
    {
        return $this->model
            ->whereHas('roles', function ($query) use ($roleName) {
                $query->where('name', $roleName);
            })
            ->with('roles')
            ->get();
    }

    /**
     * Get users by tenant
     */
    public function getUsersByTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->with('roles')
            ->paginate($perPage);
    }

    /**
     * Create user with roles
     */
    public function createWithRoles(array $data, array $roleNames = []): User
    {
        $user = $this->create($data);
        
        if (!empty($roleNames)) {
            $user->assignRole($roleNames);
        }
        
        return $user->load('roles');
    }

    /**
     * Update user and sync roles
     */
    public function updateWithRoles(int $id, array $data, array $roleNames = []): User
    {
        $user = $this->update($id, $data);
        
        if (!empty($roleNames)) {
            $user->syncRoles($roleNames);
        }
        
        return $user->load('roles');
    }
}
