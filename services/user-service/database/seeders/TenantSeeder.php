<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'name'     => 'Acme Corporation',
                'slug'     => 'acme',
                'domain'   => 'acme.saas.local',
                'plan'     => 'enterprise',
                'status'   => 'active',
                'max_users'=> 500,
                'settings' => ['features' => ['inventory', 'reporting', 'api_access']],
                'metadata' => ['industry' => 'manufacturing'],
            ],
            [
                'name'     => 'Beta Startup',
                'slug'     => 'beta-startup',
                'domain'   => 'beta.saas.local',
                'plan'     => 'starter',
                'status'   => 'active',
                'max_users'=> 10,
                'settings' => ['features' => ['inventory']],
                'metadata' => ['industry' => 'technology'],
            ],
            [
                'name'     => 'Demo Tenant',
                'slug'     => 'demo',
                'domain'   => null,
                'plan'     => 'free',
                'status'   => 'active',
                'max_users'=> 3,
                'settings' => [],
                'metadata' => [],
            ],
        ];

        foreach ($tenants as $data) {
            Tenant::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $this->command->info('Tenants seeded.');
    }
}
