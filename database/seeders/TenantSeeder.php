<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Tenancy\Entities\Tenant;

/**
 * Tenant Seeder
 *
 * Seeds demo tenant data for testing and development.
 */
class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding tenants...');

        // Create main demo tenant
        Tenant::factory()->create([
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'domain' => 'acme.example.com',
            'database' => 'tenant_acme',
            'schema' => 'acme',
            'status' => 'active',
            'settings' => [
                'timezone' => 'America/New_York',
                'locale' => 'en',
                'currency' => 'USD',
                'date_format' => 'm/d/Y',
                'time_format' => 'h:i A',
                'fiscal_year_start' => '01-01',
                'company' => [
                    'name' => 'Acme Corporation',
                    'email' => 'info@acme.example.com',
                    'phone' => '+1-555-0123',
                    'website' => 'https://acme.example.com',
                    'tax_id' => 'US-1234567',
                    'registration_number' => 'REG-12345678',
                ],
                'features' => [
                    'multi_currency' => true,
                    'multi_location' => true,
                    'advanced_reporting' => true,
                    'api_access' => true,
                    'custom_fields' => true,
                ],
                'notifications' => [
                    'email_enabled' => true,
                    'sms_enabled' => true,
                    'slack_enabled' => false,
                ],
            ],
        ]);

        // Create trial tenant
        Tenant::factory()->onTrial()->create([
            'name' => 'TechStart Solutions',
            'slug' => 'techstart',
            'domain' => 'techstart.example.com',
        ]);

        // Create small business tenant
        Tenant::factory()->smallBusiness()->create([
            'name' => 'Local Retail Shop',
            'slug' => 'localretail',
            'domain' => 'localretail.example.com',
        ]);

        // Create enterprise tenant
        Tenant::factory()->enterprise()->create([
            'name' => 'Global Enterprises Inc',
            'slug' => 'globalenterprises',
            'domain' => 'globalenterprises.example.com',
        ]);

        // Create additional random tenant
        Tenant::factory()->create();

        $this->command->info('Tenants seeded successfully!');
    }
}
