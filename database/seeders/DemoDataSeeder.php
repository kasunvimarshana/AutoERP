<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Demo Data Seeder
 *
 * Main seeder that orchestrates all demo data seeding in correct order.
 * This seeder populates the database with realistic demo data for
 * development, testing, and demonstration purposes.
 */
class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('===========================================');
        $this->command->info('Starting Demo Data Seeding...');
        $this->command->info('===========================================');

        // Seed in correct order respecting foreign key dependencies

        // 1. Seed Tenants (no dependencies)
        $this->command->newLine();
        $this->command->info('Step 1/4: Seeding Tenants...');
        $this->call(TenantSeeder::class);

        // 2. Seed Customers (depends on Tenants)
        $this->command->newLine();
        $this->command->info('Step 2/4: Seeding Customers...');
        $this->call(CustomerSeeder::class);

        // 3. Seed Leads (depends on Tenants, optionally Customers)
        $this->command->newLine();
        $this->command->info('Step 3/4: Seeding Leads...');
        $this->call(LeadSeeder::class);

        // 4. Seed Sales Orders and Lines (depends on Tenants and Customers)
        $this->command->newLine();
        $this->command->info('Step 4/4: Seeding Sales Orders...');
        $this->call(SalesOrderSeeder::class);

        // Summary
        $this->command->newLine();
        $this->command->info('===========================================');
        $this->command->info('Demo Data Seeding Completed Successfully!');
        $this->command->info('===========================================');
        $this->printSummary();
    }

    /**
     * Print seeding summary.
     */
    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->table(
            ['Entity', 'Count', 'Description'],
            [
                ['Tenants', '5', 'Demo organizations with various subscription types'],
                ['Customers', '35', 'Mix of VIP, wholesale, company, and individual customers'],
                ['Leads', '20-24', 'Leads in various stages of the sales pipeline'],
                ['Sales Orders', '16+', 'Orders in different statuses with order lines'],
            ]
        );

        $this->command->newLine();
        $this->command->info('You can now:');
        $this->command->line('  • View tenants at: /api/v1/tenants');
        $this->command->line('  • View customers at: /api/v1/customers');
        $this->command->line('  • View leads at: /api/v1/leads');
        $this->command->line('  • View sales orders at: /api/v1/sales-orders');
        $this->command->newLine();
    }
}
