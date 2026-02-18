<?php

declare(strict_types=1);

namespace App\Services\IAM;

use App\Repositories\IAM\RoleRepository;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleService
{
    public function __construct(
        private RoleRepository $roleRepository
    ) {}

    /**
     * Get all roles
     */
    public function getAllRoles(): Collection
    {
        return $this->roleRepository->getAllWithPermissions();
    }

    /**
     * Get role by ID
     */
    public function getRoleById(int $id): ?Role
    {
        return $this->roleRepository->find($id);
    }

    /**
     * Get role by name
     */
    public function getRoleByName(string $name): ?Role
    {
        return $this->roleRepository->findByName($name);
    }

    /**
     * Create new role
     */
    public function createRole(array $data): Role
    {
        // Extract permissions if provided
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        // Ensure guard_name is set
        if (!isset($data['guard_name'])) {
            $data['guard_name'] = 'web';
        }

        return $this->roleRepository->createWithPermissions($data, $permissions);
    }

    /**
     * Update role
     */
    public function updateRole(int $id, array $data): Role
    {
        // Extract permissions if provided
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        return $this->roleRepository->updateWithPermissions($id, $data, $permissions);
    }

    /**
     * Delete role
     */
    public function deleteRole(int $id): bool
    {
        return $this->roleRepository->delete($id);
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(int $roleId, array $permissionNames): Role
    {
        $role = $this->roleRepository->find($roleId);
        $role->syncPermissions($permissionNames);
        
        return $role->load('permissions');
    }

    /**
     * Get role users
     */
    public function getRoleUsers(int $roleId): Collection
    {
        return $this->roleRepository->getRoleUsers($roleId);
    }

    /**
     * Check if role exists
     */
    public function roleExists(string $name): bool
    {
        return $this->roleRepository->findByName($name) !== null;
    }
}
