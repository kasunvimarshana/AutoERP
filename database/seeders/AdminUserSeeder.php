<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * AdminUserSeeder
 *
 * Seeds default admin user for development/testing
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        // Only run in local/development environment
        if (! app()->environment(['local', 'development', 'testing'])) {
            $this->command->error('⚠️  AdminUserSeeder is only for development/testing environments!');
            $this->command->error('⚠️  For production, create admin users manually with secure passwords.');
            
            return;
        }

        // Get the first tenant (ACME Corp from DevelopmentTenantSeeder)
        $tenant = DB::table('tenants')->where('slug', 'acme')->first();

        if (! $tenant) {
            $this->command->warn('No tenant found. Please run DevelopmentTenantSeeder first.');

            return;
        }

        // Get root organization
        $organization = DB::table('organizations')
            ->where('tenant_id', $tenant->id)
            ->whereNull('parent_id')
            ->first();

        if (! $organization) {
            $this->command->warn('No root organization found.');

            return;
        }

        // Check if admin user already exists
        $existingUser = DB::table('users')
            ->where('tenant_id', $tenant->id)
            ->where('email', 'admin@acme.example.com')
            ->exists();

        if ($existingUser) {
            $this->command->warn('Admin user already exists.');

            return;
        }

        $now = now();
        $userId = Str::uuid()->toString();

        // Use environment variable for password or generate random one
        $defaultPassword = env('SEED_ADMIN_PASSWORD', Str::random(16));

        // Create admin user
        DB::table('users')->insert([
            'id' => $userId,
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'name' => 'System Administrator',
            'email' => 'admin@acme.example.com',
            'email_verified_at' => $now,
            'password' => Hash::make($defaultPassword),
            'metadata' => json_encode([
                'phone' => '+1-555-0100',
                'position' => 'System Administrator',
            ]),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->command->info('✓ Created admin user:');
        $this->command->info('  Email: admin@acme.example.com');
        $this->command->info('  Password: '.$defaultPassword);
        $this->command->warn('  ⚠️  CHANGE THIS PASSWORD IMMEDIATELY!');
        $this->command->info('  Tenant: '.$tenant->name);
        $this->command->info('  Organization: '.$organization->name);

        // Get super_admin role
        $superAdminRole = DB::table('roles')
            ->where('tenant_id', $tenant->id)
            ->where('slug', 'super_admin')
            ->first();

        if ($superAdminRole) {
            // Assign super_admin role to user
            DB::table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $superAdminRole->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->command->info('✓ Assigned Super Administrator role');
        } else {
            $this->command->warn('Super Administrator role not found. Please run DefaultRolesAndPermissionsSeeder first.');
        }

        // Create additional test users
        $this->createTestUsers($tenant->id, $organization->id);
    }

    /**
     * Create additional test users with different roles
     */
    private function createTestUsers(string $tenantId, string $organizationId): void
    {
        $now = now();

        // Use same password as admin or generate random
        $defaultPassword = env('SEED_ADMIN_PASSWORD', Str::random(16));

        $testUsers = [
            [
                'name' => 'John Manager',
                'email' => 'manager@acme.example.com',
                'role_slug' => 'manager',
                'position' => 'Sales Manager',
            ],
            [
                'name' => 'Jane User',
                'email' => 'user@acme.example.com',
                'role_slug' => 'user',
                'position' => 'Sales Representative',
            ],
        ];

        foreach ($testUsers as $userData) {
            $userId = Str::uuid()->toString();

            DB::table('users')->insert([
                'id' => $userId,
                'tenant_id' => $tenantId,
                'organization_id' => $organizationId,
                'name' => $userData['name'],
                'email' => $userData['email'],
                'email_verified_at' => $now,
                'password' => Hash::make($defaultPassword),
                'metadata' => json_encode([
                    'position' => $userData['position'],
                ]),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Assign role
            $role = DB::table('roles')
                ->where('tenant_id', $tenantId)
                ->where('slug', $userData['role_slug'])
                ->first();

            if ($role) {
                DB::table('user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => $role->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->command->info("✓ Created test user: {$userData['email']} ({$userData['role_slug']}) - Password: {$defaultPassword}");
        }
    }
}
