<?php

declare(strict_types=1);

namespace Modules\Tenancy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tenancy\Entities\Tenant;

/**
 * Tenancy Module Database Seeder
 *
 * Seeds tenant data for development and testing environments.
 * Creates demo tenants with various plans and statuses.
 */
class TenancyDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Tenancy module...');

        // Create demo tenants with different plans and statuses
        $this->createDemoTenants();

        // Create trial tenants
        $this->createTrialTenants();

        // Create suspended tenant for testing
        $this->createSuspendedTenant();

        $this->command->info('Tenancy module seeded successfully!');
    }

    /**
     * Create demo tenants with active subscriptions
     */
    private function createDemoTenants(): void
    {
        $demos = [
            [
                'name' => 'Acme Corporation',
                'subdomain' => 'acme',
                'domain' => 'acme-corp.com',
                'plan' => 'enterprise',
                'status' => 'active',
                'settings' => [
                    'timezone' => 'America/New_York',
                    'currency' => 'USD',
                    'locale' => 'en',
                    'date_format' => 'Y-m-d',
                    'time_format' => 'H:i:s',
                ],
                'features' => [
                    'sales' => true,
                    'inventory' => true,
                    'accounting' => true,
                    'hr' => true,
                    'procurement' => true,
                    'multi_currency' => true,
                    'multi_language' => true,
                    'advanced_reporting' => true,
                    'api_access' => true,
                    'custom_branding' => true,
                    'max_users' => 100,
                    'max_storage_gb' => 500,
                ],
            ],
            [
                'name' => 'Tech Startup Inc',
                'subdomain' => 'techstartup',
                'domain' => 'techstartup.io',
                'plan' => 'professional',
                'status' => 'active',
                'settings' => [
                    'timezone' => 'America/Los_Angeles',
                    'currency' => 'USD',
                    'locale' => 'en',
                    'date_format' => 'm/d/Y',
                    'time_format' => 'h:i A',
                ],
                'features' => [
                    'sales' => true,
                    'inventory' => true,
                    'accounting' => true,
                    'hr' => false,
                    'procurement' => false,
                    'multi_currency' => false,
                    'multi_language' => false,
                    'advanced_reporting' => true,
                    'api_access' => true,
                    'custom_branding' => false,
                    'max_users' => 25,
                    'max_storage_gb' => 100,
                ],
            ],
            [
                'name' => 'Small Business LLC',
                'subdomain' => 'smallbiz',
                'domain' => 'smallbiz.local',
                'plan' => 'basic',
                'status' => 'active',
                'settings' => [
                    'timezone' => 'America/Chicago',
                    'currency' => 'USD',
                    'locale' => 'en',
                    'date_format' => 'm/d/Y',
                    'time_format' => 'h:i A',
                ],
                'features' => [
                    'sales' => true,
                    'inventory' => true,
                    'accounting' => false,
                    'hr' => false,
                    'procurement' => false,
                    'multi_currency' => false,
                    'multi_language' => false,
                    'advanced_reporting' => false,
                    'api_access' => false,
                    'custom_branding' => false,
                    'max_users' => 5,
                    'max_storage_gb' => 10,
                ],
            ],
        ];

        foreach ($demos as $demoData) {
            Tenant::create($demoData);
            $this->command->info("  ✓ Created {$demoData['plan']} tenant: {$demoData['name']}");
        }
    }

    /**
     * Create trial tenants for testing trial workflows
     */
    private function createTrialTenants(): void
    {
        $trials = [
            [
                'name' => 'Trial Company Alpha',
                'subdomain' => 'trialalpha',
                'domain' => 'trial-alpha.test',
                'plan' => 'trial',
                'status' => 'trial',
                'settings' => [
                    'timezone' => 'UTC',
                    'currency' => 'USD',
                    'locale' => 'en',
                    'trial_ends_at' => now()->addDays(14)->toDateTimeString(),
                ],
                'features' => [
                    'sales' => true,
                    'inventory' => true,
                    'accounting' => true,
                    'hr' => true,
                    'procurement' => true,
                    'multi_currency' => true,
                    'multi_language' => true,
                    'advanced_reporting' => true,
                    'api_access' => true,
                    'custom_branding' => false,
                    'max_users' => 10,
                    'max_storage_gb' => 50,
                ],
            ],
            [
                'name' => 'Trial Company Beta',
                'subdomain' => 'trialbeta',
                'domain' => 'trial-beta.test',
                'plan' => 'trial',
                'status' => 'trial',
                'settings' => [
                    'timezone' => 'UTC',
                    'currency' => 'EUR',
                    'locale' => 'en',
                    'trial_ends_at' => now()->addDays(7)->toDateTimeString(),
                ],
                'features' => [
                    'sales' => true,
                    'inventory' => true,
                    'accounting' => true,
                    'hr' => false,
                    'procurement' => false,
                    'multi_currency' => false,
                    'multi_language' => false,
                    'advanced_reporting' => false,
                    'api_access' => false,
                    'custom_branding' => false,
                    'max_users' => 5,
                    'max_storage_gb' => 10,
                ],
            ],
        ];

        foreach ($trials as $trialData) {
            Tenant::create($trialData);
            $this->command->info("  ✓ Created trial tenant: {$trialData['name']}");
        }
    }

    /**
     * Create suspended tenant for testing status transitions
     */
    private function createSuspendedTenant(): void
    {
        $suspended = [
            'name' => 'Suspended Company',
            'subdomain' => 'suspended',
            'domain' => 'suspended.test',
            'plan' => 'basic',
            'status' => 'suspended',
            'settings' => [
                'timezone' => 'UTC',
                'currency' => 'USD',
                'locale' => 'en',
                'suspended_at' => now()->subDays(7)->toDateTimeString(),
                'suspension_reason' => 'Payment failure',
            ],
            'features' => [
                'sales' => false,
                'inventory' => false,
                'accounting' => false,
                'hr' => false,
                'procurement' => false,
                'multi_currency' => false,
                'multi_language' => false,
                'advanced_reporting' => false,
                'api_access' => false,
                'custom_branding' => false,
                'max_users' => 0,
                'max_storage_gb' => 0,
            ],
        ];

        Tenant::create($suspended);
        $this->command->info("  ✓ Created suspended tenant: {$suspended['name']}");
    }
}
