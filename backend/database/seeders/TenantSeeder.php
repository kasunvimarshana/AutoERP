<?php

namespace Database\Seeders;

use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::firstOrCreate(
            ['slug' => 'acme'],
            [
                'name'      => 'Acme Corp',
                'domain'    => 'acme.example.com',
                'is_active' => true,
                'plan'      => 'pro',
                'settings'  => ['webhook_url' => null, 'timezone' => 'UTC'],
                'metadata'  => ['industry' => 'Manufacturing'],
            ]
        );

        Tenant::firstOrCreate(
            ['slug' => 'beta'],
            [
                'name'      => 'Beta Corp',
                'domain'    => 'beta.example.com',
                'is_active' => true,
                'plan'      => 'basic',
                'settings'  => ['webhook_url' => null, 'timezone' => 'UTC'],
                'metadata'  => ['industry' => 'Retail'],
            ]
        );
    }
}
