<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default tenant
        $tenant = Tenant::create([
            'name' => 'Default Tenant',
            'slug' => 'default',
            'domain' => 'localhost',
            'settings' => ['theme' => 'default'],
            'is_active' => true,
            'plan' => 'enterprise',
        ]);

        app()->instance('current_tenant_id', $tenant->id);

        // Create admin user
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'roles' => ['admin'],
            'attributes' => [
                'department' => 'IT',
                'region' => 'US',
                'clearance_level' => 5,
            ],
            'is_active' => true,
        ]);
    }
}
