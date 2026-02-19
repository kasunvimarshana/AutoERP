<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * DefaultRolesAndPermissionsSeeder
 *
 * Seeds default roles and permissions for RBAC
 */
class DefaultRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        $tenants = DB::table('tenants')->get();

        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Please run DevelopmentTenantSeeder first.');

            return;
        }

        foreach ($tenants as $tenant) {
            $this->seedRolesAndPermissionsForTenant($tenant->id);
        }

        $this->command->info('Default roles and permissions seeded successfully!');
    }

    /**
     * Seed roles and permissions for a specific tenant
     */
    private function seedRolesAndPermissionsForTenant(string $tenantId): void
    {
        $now = now();

        // Define resources and actions
        $resources = [
            'tenants', 'organizations', 'users', 'roles', 'permissions',
            'products', 'product_categories', 'units', 'prices',
            'audit_logs', 'reports', 'settings',
        ];

        $actions = ['view', 'create', 'update', 'delete'];

        // Create permissions
        $permissions = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissionId = Str::uuid()->toString();
                $slug = "{$action}_{$resource}";

                DB::table('permissions')->insert([
                    'id' => $permissionId,
                    'tenant_id' => $tenantId,
                    'name' => ucfirst($action).' '.ucfirst(str_replace('_', ' ', $resource)),
                    'slug' => $slug,
                    'description' => "Permission to {$action} {$resource}",
                    'resource' => $resource,
                    'action' => $action,
                    'metadata' => json_encode([]),
                    'is_system' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $permissions[$slug] = $permissionId;
            }
        }

        $this->command->info('  ✓ Created '.count($permissions).' permissions');

        // Create roles
        $roles = [
            [
                'name' => 'Super Administrator',
                'slug' => 'super_admin',
                'description' => 'Full system access with all permissions',
                'permissions' => array_values($permissions), // All permissions
            ],
            [
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Administrative access with most permissions',
                'permissions' => array_values(array_filter($permissions, function ($slug) {
                    // Exclude tenant management from regular admin
                    return ! str_contains($slug, 'tenants');
                }, ARRAY_FILTER_USE_KEY)),
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Management access with view and create permissions',
                'permissions' => array_values(array_filter($permissions, function ($slug) {
                    return str_contains($slug, 'view_') || str_contains($slug, 'create_');
                }, ARRAY_FILTER_USE_KEY)),
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Basic user access with view permissions',
                'permissions' => array_values(array_filter($permissions, function ($slug) {
                    return str_contains($slug, 'view_');
                }, ARRAY_FILTER_USE_KEY)),
            ],
        ];

        foreach ($roles as $roleData) {
            $roleId = Str::uuid()->toString();

            DB::table('roles')->insert([
                'id' => $roleId,
                'tenant_id' => $tenantId,
                'name' => $roleData['name'],
                'slug' => $roleData['slug'],
                'description' => $roleData['description'],
                'metadata' => json_encode([]),
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Attach permissions to role
            foreach ($roleData['permissions'] as $permissionId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->command->info("  ✓ Created role: {$roleData['name']} with ".count($roleData['permissions']).' permissions');
        }
    }
}
