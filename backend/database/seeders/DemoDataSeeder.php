<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Organization\Models\Organization;
use App\Modules\Organization\Models\Branch;
use App\Models\User;
use App\Modules\IAM\Models\Role;
use App\Modules\IAM\Models\Permission;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo tenant
        $tenant = Tenant::create([
            'name' => 'Demo Company',
            'slug' => 'demo-company',
            'domain' => 'demo.autoerp.com',
            'email' => 'admin@demo.com',
            'phone' => '+1234567890',
            'address' => '123 Demo Street',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'locale' => 'en',
            'is_active' => true,
        ]);

        // Create demo organization
        $organization = Organization::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Organization',
            'code' => 'DEMO-ORG',
            'type' => 'internal',
            'email' => 'org@demo.com',
            'phone' => '+1234567890',
            'address' => '123 Demo Street',
            'city' => 'Demo City',
            'country' => 'US',
            'is_active' => true,
        ]);

        // Create demo branch
        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'type' => 'warehouse',
            'email' => 'main@demo.com',
            'phone' => '+1234567890',
            'address' => '123 Demo Street',
            'city' => 'Demo City',
            'country' => 'US',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
            'is_primary' => true,
        ]);

        // Create admin role
        $adminRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Administrator',
            'slug' => 'admin',
            'description' => 'Full system access',
            'level' => 100,
            'is_system' => true,
            'is_active' => true,
        ]);

        // Create demo user
        $user = User::create([
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
            'name' => 'Demo Admin',
            'username' => 'admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('password'),
            'phone' => '+1234567890',
            'is_active' => true,
            'is_verified' => true,
        ]);

        // Assign admin role to user
        $user->roles()->attach($adminRole->id);

        $this->command->info('Demo data created successfully!');
        $this->command->info('Email: admin@demo.com');
        $this->command->info('Password: password');
    }
}
