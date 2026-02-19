<?php

declare(strict_types=1);

namespace Modules\JobCard\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * JobCard Database Seeder
 *
 * Main seeder for JobCard module
 */
class JobCardDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            JobCardPermissionsSeeder::class,
        ]);

        $this->command->info('JobCard module seeded successfully!');
    }
}
