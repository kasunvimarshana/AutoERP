<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\TenantManagement\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create default tenant
        $tenant = Tenant::create([
            'name' => 'Demo Tenant',
            'slug' => 'demo-tenant',
            'domain' => null,
            'status' => 'active',
            'subscription_status' => 'trial',
            'subscription_plan' => 'starter',
            'subscription_started_at' => now(),
            'subscription_expires_at' => now()->addMonths(1),
            'max_users' => 50,
            'max_branches' => 5,
            'settings' => [
                'timezone' => 'UTC',
                'currency' => 'USD',
                'date_format' => 'Y-m-d',
            ],
        ]);

        // Create super admin user
        $superAdmin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Super Administrator',
            'email' => 'admin@autoerp.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $superAdmin->assignRole('super_admin');

        // Create admin user
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $admin->assignRole('admin');

        // Create manager user
        $manager = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager User',
            'email' => 'manager@demo.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $manager->assignRole('manager');

        $this->command->info('Initial data seeded successfully!');
        $this->command->info('Super Admin: admin@autoerp.com / password123');
        $this->command->info('Admin: admin@demo.com / password123');
        $this->command->info('Manager: manager@demo.com / password123');
    }
}
