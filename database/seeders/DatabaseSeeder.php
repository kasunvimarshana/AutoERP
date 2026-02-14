<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // Order matters due to foreign key constraints:
        // 1. Tenant - must be first as it's referenced by all other tables
        // 2. RolePermission - independent table, needs to be created before users get roles
        // 3. User - depends on Tenant and will be assigned roles
        // 4. Branch - depends on Tenant and User (manager_id)
        // 5. Customer - depends on Tenant
        // 6. Product - depends on Tenant

        $this->call([
            TenantSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            BranchSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('Database seeding completed successfully!');
        $this->command->newLine();
        $this->command->info('Default credentials for all tenants:');
        $this->command->info('  Email: admin@{subdomain}.com');
        $this->command->info('  Password: password');
        $this->command->newLine();
        $this->command->info('Available tenants:');
        $this->command->info('  - acme (Acme Corp)');
        $this->command->info('  - techstart (TechStart Inc)');
        $this->command->info('  - globaltrade (Global Trade Ltd)');
        $this->command->newLine();
        $this->command->info('Available roles per tenant:');
        $this->command->info('  - admin@{subdomain}.com → Super Admin');
        $this->command->info('  - manager@{subdomain}.com → Manager');
        $this->command->info('  - sales@{subdomain}.com → Sales');
        $this->command->info('  - inventory@{subdomain}.com → Inventory Manager');
        $this->command->info('  - cashier@{subdomain}.com → Cashier');
        $this->command->info('  - viewer@{subdomain}.com → Viewer');
    }
}
