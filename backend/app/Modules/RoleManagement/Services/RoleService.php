<?php

namespace App\Modules\RoleManagement\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleService
{
    public function getAllRoles()
    {
        return Role::with('permissions')->get();
    }

    public function findById(int $id)
    {
        return Role::with('permissions')->findOrFail($id);
    }

    public function create(array $data)
    {
        try {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? 'web',
            ]);

            if (!empty($data['permissions'])) {
                $role->givePermissionTo($data['permissions']);
            }

            Log::info('Role created', ['role_id' => $role->id, 'name' => $role->name]);

            return $role->load('permissions');
        } catch (\Exception $e) {
            Log::error('Failed to create role', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        try {
            $role = Role::findOrFail($id);
            
            if (isset($data['name'])) {
                $role->name = $data['name'];
                $role->save();
            }

            if (isset($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            Log::info('Role updated', ['role_id' => $id]);

            return $role->load('permissions');
        } catch (\Exception $e) {
            Log::error('Failed to update role', ['role_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();

            Log::info('Role deleted', ['role_id' => $id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete role', ['role_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function assignPermissions(int $roleId, array $permissions)
    {
        try {
            $role = Role::findOrFail($roleId);
            $role->syncPermissions($permissions);

            Log::info('Role permissions updated', ['role_id' => $roleId, 'permissions' => $permissions]);

            return $role->load('permissions');
        } catch (\Exception $e) {
            Log::error('Failed to assign permissions to role', ['role_id' => $roleId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
