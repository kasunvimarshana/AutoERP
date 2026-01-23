<?php

namespace App\Modules\RoleManagement\Services;

use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function getAllPermissions()
    {
        return Permission::all();
    }

    public function findById(int $id)
    {
        return Permission::findOrFail($id);
    }

    public function create(array $data)
    {
        try {
            $permission = Permission::create([
                'name' => $data['name'],
                'guard_name' => $data['guard_name'] ?? 'web',
            ]);

            Log::info('Permission created', ['permission_id' => $permission->id, 'name' => $permission->name]);

            return $permission;
        } catch (\Exception $e) {
            Log::error('Failed to create permission', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(int $id, array $data)
    {
        try {
            $permission = Permission::findOrFail($id);
            
            if (isset($data['name'])) {
                $permission->name = $data['name'];
                $permission->save();
            }

            Log::info('Permission updated', ['permission_id' => $id]);

            return $permission;
        } catch (\Exception $e) {
            Log::error('Failed to update permission', ['permission_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $permission = Permission::findOrFail($id);
            $permission->delete();

            Log::info('Permission deleted', ['permission_id' => $id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete permission', ['permission_id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getGroupedPermissions()
    {
        $permissions = Permission::all();
        
        $grouped = [];
        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'general';
            
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }
}
