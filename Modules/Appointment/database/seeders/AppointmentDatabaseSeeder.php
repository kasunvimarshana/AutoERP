<?php

declare(strict_types=1);

namespace Modules\Appointment\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Appointment Database Seeder
 *
 * Main seeder for Appointment module
 */
class AppointmentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
        ]);
    }
}
