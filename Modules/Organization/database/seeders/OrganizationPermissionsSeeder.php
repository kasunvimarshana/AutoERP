<?php

declare(strict_types=1);

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Organization Permissions Seeder
 *
 * Creates permissions and assigns them to roles for the Organization module
 */
class OrganizationPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define Organization permissions
        $organizationPermissions = [
            'organization.list' => 'List all organizations',
            'organization.read' => 'View organization details',
            'organization.create' => 'Create new organization',
            'organization.update' => 'Update organization',
            'organization.delete' => 'Delete organization',
        ];

        // Define Branch permissions
        $branchPermissions = [
            'branch.list' => 'List all branches',
            'branch.read' => 'View branch details',
            'branch.create' => 'Create new branch',
            'branch.update' => 'Update branch',
            'branch.delete' => 'Delete branch',
        ];

        // Combine all permissions
        $allPermissions = array_merge($organizationPermissions, $branchPermissions);

        // Create permissions if they don't exist
        foreach ($allPermissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles($organizationPermissions, $branchPermissions);

        $this->command->info('Organization module permissions created successfully.');
    }

    /**
     * Assign permissions to roles
     */
    protected function assignPermissionsToRoles(array $organizationPermissions, array $branchPermissions): void
    {
        // Get or create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $user = Role::firstOrCreate(['name' => 'user']);

        // Super Admin gets all permissions
        $superAdmin->givePermissionTo(array_keys(array_merge($organizationPermissions, $branchPermissions)));

        // Admin gets all permissions
        $admin->givePermissionTo(array_keys(array_merge($organizationPermissions, $branchPermissions)));

        // Manager gets read and limited write permissions
        $manager->givePermissionTo([
            'organization.list',
            'organization.read',
            'branch.list',
            'branch.read',
            'branch.create',
            'branch.update',
        ]);

        // User gets only read permissions
        $user->givePermissionTo([
            'organization.list',
            'organization.read',
            'branch.list',
            'branch.read',
        ]);
    }
}
