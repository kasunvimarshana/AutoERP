<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Enterprise ERP/CRM Database...');
        $this->command->info('');

        // Seed in correct order to respect foreign key constraints
        $this->call([
            DevelopmentTenantSeeder::class,
            DefaultUnitsSeeder::class,
            DefaultRolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('📝 Default Credentials:');
        $this->command->info('   Email: admin@acme.example.com');
        $this->command->info('   Password: password');
        $this->command->info('   Tenant: acme (ACME Corporation)');
        $this->command->info('');
        $this->command->warn('⚠️  Please change the default password immediately!');
    }
}
