<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Default demo tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name'          => 'Demo Company',
                'domain'        => 'demo.example.com',
                'plan'          => 'professional',
                'status'        => 'active',
                'max_users'     => 50,
                'max_products'  => 1000,
                'subscribed_at' => now(),
                'settings'      => [
                    'timezone'        => 'UTC',
                    'currency'        => 'USD',
                    'single_session'  => false,
                    'low_stock_alert' => true,
                ],
            ]
        );

        // Seed tenant configurations
        $configurations = [
            ['key' => 'order_prefix',       'value' => 'DEMO',  'type' => 'string',  'group' => 'orders'],
            ['key' => 'max_order_items',     'value' => '50',    'type' => 'integer', 'group' => 'orders'],
            ['key' => 'tax_rate',            'value' => '0.08',  'type' => 'float',   'group' => 'billing'],
            ['key' => 'low_stock_threshold', 'value' => '10',    'type' => 'integer', 'group' => 'inventory'],
            ['key' => 'allow_backorder',     'value' => 'false', 'type' => 'boolean', 'group' => 'inventory'],
        ];

        foreach ($configurations as $config) {
            TenantConfiguration::firstOrCreate(
                ['tenant_id' => $tenant->id, 'key' => $config['key']],
                array_merge($config, ['tenant_id' => $tenant->id])
            );
        }

        // Bind current tenant for seeding
        app()->instance('current_tenant', $tenant);

        // Create admin user for the demo tenant
        $admin = User::firstOrCreate(
            ['email' => 'admin@demo.example.com', 'tenant_id' => $tenant->id],
            [
                'name'              => 'Demo Admin',
                'password'          => Hash::make('Password123!'),
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Create a regular user
        $user = User::firstOrCreate(
            ['email' => 'user@demo.example.com', 'tenant_id' => $tenant->id],
            [
                'name'              => 'Demo User',
                'password'          => Hash::make('Password123!'),
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('admin');
        $user->assignRole('user');

        $this->command->info("Tenant '{$tenant->name}' seeded with admin and user accounts.");
    }
}
