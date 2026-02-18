<?php

declare(strict_types=1);

namespace App\Repositories\IAM;

use App\Repositories\BaseRepository;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository extends BaseRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    /**
     * Get roles with their permissions
     */
    public function getAllWithPermissions(): Collection
    {
        return $this->model
            ->with('permissions')
            ->get();
    }

    /**
     * Find role by name
     */
    public function findByName(string $name): ?Role
    {
        return $this->model
            ->where('name', $name)
            ->first();
    }

    /**
     * Create role with permissions
     */
    public function createWithPermissions(array $data, array $permissionNames = []): Role
    {
        $role = $this->create($data);
        
        if (!empty($permissionNames)) {
            $role->givePermissionTo($permissionNames);
        }
        
        return $role->load('permissions');
    }

    /**
     * Update role and sync permissions
     */
    public function updateWithPermissions(int $id, array $data, array $permissionNames = []): Role
    {
        $role = $this->update($id, $data);
        
        if (!empty($permissionNames)) {
            $role->syncPermissions($permissionNames);
        }
        
        return $role->load('permissions');
    }

    /**
     * Get role's users
     */
    public function getRoleUsers(int $roleId): Collection
    {
        $role = $this->find($roleId);
        
        return $role ? $role->users : collect();
    }
}
