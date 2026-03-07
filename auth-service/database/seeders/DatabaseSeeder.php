<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the database with a default tenant, roles, permissions, and admin user.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Create default permissions ────────────────────────────────
        $permissions = [
            // Inventory permissions
            'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete',
            // Product permissions
            'product.view', 'product.create', 'product.update', 'product.delete',
            // Order permissions
            'order.view', 'order.create', 'order.update', 'order.cancel',
            // Tenant permissions (super-admin only)
            'tenant.view', 'tenant.create', 'tenant.update', 'tenant.delete',
            // User permissions
            'user.view', 'user.create', 'user.update', 'user.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // ── Global roles ────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        $superAdmin->syncPermissions(Permission::all());

        // ── Default tenant ──────────────────────────────────────────
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'default'],
            [
                'name'      => 'Default Tenant',
                'plan'      => 'enterprise',
                'is_active' => true,
            ]
        );

        // Tenant-scoped roles
        $adminRole   = Role::firstOrCreate(['name' => "admin:{$tenant->id}", 'guard_name' => 'api']);
        $managerRole = Role::firstOrCreate(['name' => "warehouse_manager:{$tenant->id}", 'guard_name' => 'api']);
        $staffRole   = Role::firstOrCreate(['name' => "staff:{$tenant->id}", 'guard_name' => 'api']);

        $adminRole->syncPermissions(Permission::whereIn('name', [
            'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete',
            'product.view', 'product.create', 'product.update', 'product.delete',
            'order.view', 'order.create', 'order.update', 'order.cancel',
            'user.view', 'user.create', 'user.update',
        ])->get());

        $managerRole->syncPermissions(Permission::whereIn('name', [
            'inventory.view', 'inventory.create', 'inventory.update',
            'product.view', 'product.update',
            'order.view', 'order.create',
        ])->get());

        $staffRole->syncPermissions(Permission::whereIn('name', [
            'inventory.view', 'product.view', 'order.view',
        ])->get());

        // ── Default super-admin user ────────────────────────────────
        // WARNING: Change these credentials immediately in any non-local environment.
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@saas.local'],
            [
                'tenant_id'  => $tenant->id,
                'name'       => 'Super Admin',
                'password'   => Hash::make(env('SEED_SUPERADMIN_PASSWORD', 'change-me-in-production!')),
                'is_active'  => true,
                'attributes' => ['department' => 'IT', 'clearance' => 'top-secret'],
            ]
        );
        $superAdminUser->assignRole('super-admin');

        // ── Default tenant admin ────────────────────────────────────
        // WARNING: Change these credentials immediately in any non-local environment.
        $tenantAdmin = User::firstOrCreate(
            ['email' => 'admin@default.local'],
            [
                'tenant_id'  => $tenant->id,
                'name'       => 'Tenant Admin',
                'password'   => Hash::make(env('SEED_ADMIN_PASSWORD', 'change-me-in-production!')),
                'is_active'  => true,
                'attributes' => ['department' => 'Management'],
            ]
        );
        $tenantAdmin->assignRole("admin:{$tenant->id}");

        $this->command->info('Auth service seeded successfully.');
    }
}
