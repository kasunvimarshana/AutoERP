<?php

declare(strict_types=1);

namespace Modules\IAM\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\IAM\Entities\Permission;
use Modules\IAM\Entities\Role;

/**
 * Role Service
 *
 * Business logic for role management using native Laravel features.
 */
class RoleService
{
    /**
     * Create a new role.
     */
    public function createRole(array $data): Role
    {
        DB::beginTransaction();
        try {
            // Auto-generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $role = Role::create($data);

            // Assign permissions if provided
            if (! empty($data['permission_ids'])) {
                $role->rolePermissions()->sync($data['permission_ids']);
            }

            DB::commit();

            return $role->load('rolePermissions');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing role.
     */
    public function updateRole(Role $role, array $data): Role
    {
        DB::beginTransaction();
        try {
            // Prevent modification of system roles
            if ($role->is_system && isset($data['is_system']) && ! $data['is_system']) {
                throw new \RuntimeException('Cannot modify system role status');
            }

            // Update slug if name changed
            if (isset($data['name']) && $data['name'] !== $role->name) {
                $data['slug'] = Str::slug($data['name']);
            }

            $role->update($data);

            // Update permissions if provided
            if (isset($data['permission_ids'])) {
                $role->rolePermissions()->sync($data['permission_ids']);
            }

            DB::commit();

            return $role->fresh(['rolePermissions']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role): bool
    {
        DB::beginTransaction();
        try {
            // Prevent deletion of system roles
            if ($role->is_system) {
                throw new \RuntimeException('Cannot delete system role');
            }

            // Check if role has users
            if ($role->users()->exists()) {
                throw new \RuntimeException('Cannot delete role with assigned users');
            }

            // Detach all permissions
            $role->rolePermissions()->detach();

            // Delete the role
            $role->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissions(Role $role, array $permissionIds): Role
    {
        DB::beginTransaction();
        try {
            $role->rolePermissions()->sync($permissionIds);

            DB::commit();

            return $role->fresh(['rolePermissions']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Add a permission to a role.
     */
    public function addPermission(Role $role, Permission $permission): Role
    {
        $role->assignPermission($permission);

        return $role->fresh(['rolePermissions']);
    }

    /**
     * Remove a permission from a role.
     */
    public function removePermission(Role $role, Permission $permission): Role
    {
        $role->removePermission($permission);

        return $role->fresh(['rolePermissions']);
    }

    /**
     * Clone a role with a new name.
     */
    public function cloneRole(Role $role, string $newName): Role
    {
        DB::beginTransaction();
        try {
            $newRole = Role::create([
                'name' => $newName,
                'slug' => Str::slug($newName),
                'description' => $role->description ? "Copy of {$role->description}" : null,
                'parent_id' => $role->parent_id,
                'permissions' => $role->permissions,
                'is_system' => false,
                'is_active' => true,
            ]);

            // Copy permissions
            $permissionIds = $role->rolePermissions()->pluck('id')->toArray();
            $newRole->rolePermissions()->sync($permissionIds);

            DB::commit();

            return $newRole->load('rolePermissions');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
