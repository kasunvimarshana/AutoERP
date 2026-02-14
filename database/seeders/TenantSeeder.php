<?php

namespace Database\Seeders;

use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Acme Corp',
                'subdomain' => 'acme',
                'domain' => null,
                'is_active' => true,
                'settings' => [
                    'timezone' => 'America/New_York',
                    'currency' => 'USD',
                    'language' => 'en',
                ],
                'subscribed_at' => now(),
            ],
            [
                'name' => 'TechStart Inc',
                'subdomain' => 'techstart',
                'domain' => null,
                'is_active' => true,
                'settings' => [
                    'timezone' => 'America/Los_Angeles',
                    'currency' => 'USD',
                    'language' => 'en',
                ],
                'subscribed_at' => now()->subMonths(3),
                'trial_ends_at' => now()->subMonths(2),
            ],
            [
                'name' => 'Global Trade Ltd',
                'subdomain' => 'globaltrade',
                'domain' => null,
                'is_active' => true,
                'settings' => [
                    'timezone' => 'Europe/London',
                    'currency' => 'GBP',
                    'language' => 'en',
                ],
                'trial_ends_at' => now()->addDays(15),
            ],
        ];

        foreach ($tenants as $tenantData) {
            Tenant::firstOrCreate(
                ['subdomain' => $tenantData['subdomain']],
                $tenantData
            );
        }

        $this->command->info('Tenants created successfully!');
    }
}
