<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAM\Entities\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // IAM Module Permissions
            [
                'name' => 'View Permissions',
                'slug' => 'iam.permission.view',
                'module' => 'iam',
                'resource' => 'permission',
                'action' => 'view',
                'description' => 'View permissions list and details',
            ],
            [
                'name' => 'Create Permission',
                'slug' => 'iam.permission.create',
                'module' => 'iam',
                'resource' => 'permission',
                'action' => 'create',
                'description' => 'Create new permissions',
            ],
            [
                'name' => 'Update Permission',
                'slug' => 'iam.permission.update',
                'module' => 'iam',
                'resource' => 'permission',
                'action' => 'update',
                'description' => 'Update existing permissions',
            ],
            [
                'name' => 'Delete Permission',
                'slug' => 'iam.permission.delete',
                'module' => 'iam',
                'resource' => 'permission',
                'action' => 'delete',
                'description' => 'Delete permissions',
            ],
            [
                'name' => 'Assign Permissions',
                'slug' => 'iam.permission.assign',
                'module' => 'iam',
                'resource' => 'permission',
                'action' => 'assign',
                'description' => 'Assign permissions to roles and users',
            ],

            // Role Management Permissions
            [
                'name' => 'View Roles',
                'slug' => 'iam.role.view',
                'module' => 'iam',
                'resource' => 'role',
                'action' => 'view',
                'description' => 'View roles list and details',
            ],
            [
                'name' => 'Create Role',
                'slug' => 'iam.role.create',
                'module' => 'iam',
                'resource' => 'role',
                'action' => 'create',
                'description' => 'Create new roles',
            ],
            [
                'name' => 'Update Role',
                'slug' => 'iam.role.update',
                'module' => 'iam',
                'resource' => 'role',
                'action' => 'update',
                'description' => 'Update existing roles',
            ],
            [
                'name' => 'Delete Role',
                'slug' => 'iam.role.delete',
                'module' => 'iam',
                'resource' => 'role',
                'action' => 'delete',
                'description' => 'Delete roles',
            ],
            [
                'name' => 'Assign Roles',
                'slug' => 'iam.role.assign',
                'module' => 'iam',
                'resource' => 'role',
                'action' => 'assign',
                'description' => 'Assign roles to users',
            ],

            // Group Management Permissions
            [
                'name' => 'View Groups',
                'slug' => 'iam.group.view',
                'module' => 'iam',
                'resource' => 'group',
                'action' => 'view',
                'description' => 'View groups list and details',
            ],
            [
                'name' => 'Create Group',
                'slug' => 'iam.group.create',
                'module' => 'iam',
                'resource' => 'group',
                'action' => 'create',
                'description' => 'Create new groups',
            ],
            [
                'name' => 'Update Group',
                'slug' => 'iam.group.update',
                'module' => 'iam',
                'resource' => 'group',
                'action' => 'update',
                'description' => 'Update existing groups',
            ],
            [
                'name' => 'Delete Group',
                'slug' => 'iam.group.delete',
                'module' => 'iam',
                'resource' => 'group',
                'action' => 'delete',
                'description' => 'Delete groups',
            ],
            [
                'name' => 'Manage Group Members',
                'slug' => 'iam.group.manage-members',
                'module' => 'iam',
                'resource' => 'group',
                'action' => 'manage-members',
                'description' => 'Add or remove users from groups',
            ],

            // User Management Permissions
            [
                'name' => 'View Users',
                'slug' => 'iam.user.view',
                'module' => 'iam',
                'resource' => 'user',
                'action' => 'view',
                'description' => 'View users list and details',
            ],
            [
                'name' => 'Create User',
                'slug' => 'iam.user.create',
                'module' => 'iam',
                'resource' => 'user',
                'action' => 'create',
                'description' => 'Create new users',
            ],
            [
                'name' => 'Update User',
                'slug' => 'iam.user.update',
                'module' => 'iam',
                'resource' => 'user',
                'action' => 'update',
                'description' => 'Update existing users',
            ],
            [
                'name' => 'Delete User',
                'slug' => 'iam.user.delete',
                'module' => 'iam',
                'resource' => 'user',
                'action' => 'delete',
                'description' => 'Delete users',
            ],
            [
                'name' => 'Manage User Permissions',
                'slug' => 'iam.user.manage-permissions',
                'module' => 'iam',
                'resource' => 'user',
                'action' => 'manage-permissions',
                'description' => 'Manage user-specific permissions',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['slug' => $permissionData['slug']],
                array_merge($permissionData, ['is_active' => true])
            );
        }

        $this->command->info('IAM permissions seeded successfully!');
    }
}
