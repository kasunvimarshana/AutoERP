<?php

namespace Modules\IAM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

class IAMSeeder extends Seeder
{
    public function run(): void
    {
        // Create system permissions
        $permissions = $this->createPermissions();

        // Create system roles
        $this->createRoles($permissions);
    }

    private function createPermissions(): array
    {
        $resources = [
            'user' => ['view', 'create', 'update', 'delete', 'assign-role'],
            'role' => ['view', 'create', 'update', 'delete', 'assign-permission'],
            'permission' => ['view', 'create', 'delete'],
        ];

        $permissions = [];

        foreach ($resources as $resource => $actions) {
            foreach ($actions as $action) {
                $name = Permission::generateName($resource, $action);

                $permission = Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web'],
                    [
                        'description' => "Permission to {$action} {$resource}",
                        'resource' => $resource,
                        'action' => $action,
                        'is_system' => true,
                    ]
                );

                $permissions[$name] = $permission;
            }
        }

        return $permissions;
    }

    private function createRoles(array $permissions): void
    {
        // Super Admin role - has all permissions
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            [
                'description' => 'Super Administrator with full access',
                'is_system' => true,
            ]
        );
        $superAdmin->syncPermissions(array_keys($permissions));

        // Admin role - has most permissions except super admin stuff
        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            [
                'description' => 'Administrator with management access',
                'is_system' => true,
            ]
        );
        $admin->syncPermissions([
            'user.view',
            'user.create',
            'user.update',
            'user.assign-role',
            'role.view',
            'permission.view',
        ]);

        // Manager role
        $manager = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => 'web'],
            [
                'description' => 'Manager with limited management access',
                'is_system' => true,
            ]
        );
        $manager->syncPermissions([
            'user.view',
            'user.update',
        ]);

        // User role - basic access
        $user = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'web'],
            [
                'description' => 'Regular user with basic access',
                'is_system' => true,
            ]
        );
        $user->syncPermissions([
            'user.view',
        ]);
    }
}
