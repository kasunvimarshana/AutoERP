<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default demo tenant
        $tenant = Tenant::create([
            'id' => Str::uuid(),
            'name' => 'Demo Company',
            'subdomain' => 'demo',
            'isolation_strategy' => 'row_level',
            'status' => 'active',
            'settings' => [
                'timezone' => 'UTC',
                'currency' => 'USD',
                'language' => 'en',
            ],
            'metadata' => [
                'company_type' => 'demo',
            ],
        ]);

        // Create admin user for demo tenant
        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@demo.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Assign admin role
        $admin->assignRole('admin');

        $this->command->info('Demo tenant created: ' . $tenant->name);
        $this->command->info('Admin user created: ' . $admin->email);
        $this->command->info('Password: password');
    }
}

