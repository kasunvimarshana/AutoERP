<?php

namespace App\Modules\IAM\Services;

use App\Modules\IAM\DTOs\RoleDTO;
use App\Modules\IAM\Models\Role;
use App\Modules\IAM\Repositories\PermissionRepository;
use App\Modules\IAM\Repositories\RoleRepository;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Role Service
 *
 * Handles business logic for role management including
 * permission assignments.
 */
class RoleService extends BaseService
{
    public function __construct(
        protected RoleRepository $roleRepository,
        protected PermissionRepository $permissionRepository
    ) {}

    /**
     * Get all roles
     */
    public function getAllRoles(?int $tenantId = null): Collection
    {
        if ($tenantId) {
            return $this->roleRepository->getByTenant($tenantId);
        }

        return $this->roleRepository->all();
    }

    /**
     * Get paginated roles
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getPaginatedRoles(int $perPage = 15)
    {
        return $this->roleRepository->paginate($perPage);
    }

    /**
     * Get role by ID
     */
    public function getRoleById(int $id): Role
    {
        return $this->roleRepository->findOrFail($id);
    }

    /**
     * Get role by slug
     */
    public function getRoleBySlug(string $slug): ?Role
    {
        return $this->roleRepository->findBySlug($slug);
    }

    /**
     * Create a new role
     *
     * @throws \Throwable
     */
    public function createRole(RoleDTO $dto): Role
    {
        return $this->transaction(function () use ($dto) {
            // Check if role with slug already exists
            if ($this->roleRepository->findBySlug($dto->slug)) {
                throw new \InvalidArgumentException('Role with this slug already exists');
            }

            $role = $this->roleRepository->create($dto->toArray());

            $this->logInfo('Role created', [
                'role_id' => $role->id,
                'slug' => $role->slug,
            ]);

            return $role;
        });
    }

    /**
     * Update role
     *
     * @throws \Throwable
     */
    public function updateRole(int $id, array $data): Role
    {
        return $this->transaction(function () use ($id, $data) {
            $role = $this->roleRepository->findOrFail($id);

            // Prevent updating system roles
            if ($role->is_system_role) {
                throw new \InvalidArgumentException('Cannot update system roles');
            }

            // Check if slug is being updated and already exists
            if (isset($data['slug'])) {
                $existingRole = $this->roleRepository->findBySlug($data['slug']);
                if ($existingRole && $existingRole->id !== $id) {
                    throw new \InvalidArgumentException('Role with this slug already exists');
                }
            }

            $this->roleRepository->update($id, $data);
            $role = $this->roleRepository->findOrFail($id);

            $this->logInfo('Role updated', [
                'role_id' => $role->id,
            ]);

            return $role;
        });
    }

    /**
     * Delete role
     *
     * @throws \Throwable
     */
    public function deleteRole(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $role = $this->roleRepository->findOrFail($id);

            // Prevent deleting system roles
            if ($role->is_system_role) {
                throw new \InvalidArgumentException('Cannot delete system roles');
            }

            $result = $this->roleRepository->delete($id);

            $this->logInfo('Role deleted', [
                'role_id' => $id,
            ]);

            return $result;
        });
    }

    /**
     * Assign permissions to role
     *
     * @throws \Throwable
     */
    public function assignPermissions(int $roleId, array $permissionIds): Role
    {
        return $this->transaction(function () use ($roleId, $permissionIds) {
            $role = $this->roleRepository->findOrFail($roleId);

            // Validate all permissions exist and fetch them
            $permissions = [];
            foreach ($permissionIds as $permissionId) {
                $permissions[] = $this->permissionRepository->findOrFail($permissionId);
            }

            // Assign each permission
            foreach ($permissions as $permission) {
                $this->roleRepository->assignPermission($role, $permission);
            }

            $this->logInfo('Permissions assigned to role', [
                'role_id' => $roleId,
                'permission_ids' => $permissionIds,
            ]);

            return $role->fresh(['permissions']);
        });
    }

    /**
     * Sync role permissions (replace all existing permissions)
     *
     * @throws \Throwable
     */
    public function syncPermissions(int $roleId, array $permissionIds): Role
    {
        return $this->transaction(function () use ($roleId, $permissionIds) {
            $role = $this->roleRepository->findOrFail($roleId);

            // Validate all permissions exist
            foreach ($permissionIds as $permissionId) {
                $this->permissionRepository->findOrFail($permissionId);
            }

            $this->roleRepository->syncPermissions($role, $permissionIds);

            $this->logInfo('Role permissions synchronized', [
                'role_id' => $roleId,
                'permission_ids' => $permissionIds,
            ]);

            return $role->fresh(['permissions']);
        });
    }

    /**
     * Get role permissions
     */
    public function getRolePermissions(int $roleId): Collection
    {
        return $this->roleRepository->getRolePermissions($roleId);
    }

    /**
     * Get role users
     */
    public function getRoleUsers(int $roleId): Collection
    {
        return $this->roleRepository->getRoleUsers($roleId);
    }

    /**
     * Get system roles
     */
    public function getSystemRoles(): Collection
    {
        return $this->roleRepository->getSystemRoles();
    }

    /**
     * Get custom roles for tenant
     */
    public function getCustomRoles(int $tenantId): Collection
    {
        return $this->roleRepository->getCustomRoles($tenantId);
    }
}
