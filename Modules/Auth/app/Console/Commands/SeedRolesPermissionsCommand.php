<?php

declare(strict_types=1);

namespace Modules\Auth\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed Roles and Permissions Command
 *
 * Creates default roles and permissions for the authentication system.
 */
class SeedRolesPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:seed-roles
                            {--fresh : Drop existing roles and permissions before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed default roles and permissions for the authentication system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding roles and permissions...');

        // Fresh start if flag is set
        if ($this->option('fresh')) {
            $this->warn('Removing existing roles and permissions...');
            Permission::query()->delete();
            Role::query()->delete();
            $this->info('Existing roles and permissions removed.');
        }

        // Define permissions
        $permissions = $this->getPermissions();

        // Create permissions
        $this->info('Creating permissions...');
        $progressBar = $this->output->createProgressBar(count($permissions));
        $progressBar->start();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info(sprintf('Created %d permissions.', count($permissions)));

        // Define and create roles
        $roles = $this->getRolesWithPermissions();

        $this->info('Creating roles and assigning permissions...');
        $progressBar = $this->output->createProgressBar(count($roles));
        $progressBar->start();

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info(sprintf('Created %d roles with permissions.', count($roles)));

        $this->newLine();
        $this->info('✓ Roles and permissions seeded successfully!');

        return Command::SUCCESS;
    }

    /**
     * Get all permissions
     *
     * @return array<string>
     */
    private function getPermissions(): array
    {
        return [
            // User permissions
            'user.create',
            'user.read',
            'user.update',
            'user.delete',
            'user.list',

            // Role permissions
            'role.create',
            'role.read',
            'role.update',
            'role.delete',
            'role.list',
            'role.assign',
            'role.revoke',

            // Permission permissions
            'permission.create',
            'permission.read',
            'permission.update',
            'permission.delete',
            'permission.list',
            'permission.assign',
            'permission.revoke',

            // Tenant permissions
            'tenant.create',
            'tenant.read',
            'tenant.update',
            'tenant.delete',
            'tenant.list',
            'tenant.switch',

            // Audit permissions
            'audit.read',
            'audit.list',
            'audit.export',
        ];
    }

    /**
     * Get roles with their permissions
     *
     * @return array<string, array<string>>
     */
    private function getRolesWithPermissions(): array
    {
        return [
            'super-admin' => [
                // All permissions
                'user.create', 'user.read', 'user.update', 'user.delete', 'user.list',
                'role.create', 'role.read', 'role.update', 'role.delete', 'role.list', 'role.assign', 'role.revoke',
                'permission.create', 'permission.read', 'permission.update', 'permission.delete', 'permission.list', 'permission.assign', 'permission.revoke',
                'tenant.create', 'tenant.read', 'tenant.update', 'tenant.delete', 'tenant.list', 'tenant.switch',
                'audit.read', 'audit.list', 'audit.export',
            ],

            'admin' => [
                'user.create', 'user.read', 'user.update', 'user.delete', 'user.list',
                'role.read', 'role.list', 'role.assign', 'role.revoke',
                'permission.read', 'permission.list',
                'tenant.read',
                'audit.read', 'audit.list',
            ],

            'manager' => [
                'user.create', 'user.read', 'user.update', 'user.list',
                'role.read', 'role.list',
                'permission.read', 'permission.list',
                'tenant.read',
                'audit.read', 'audit.list',
            ],

            'user' => [
                'user.read',
                'tenant.read',
            ],

            'guest' => [
                'tenant.read',
            ],
        ];
    }
}
