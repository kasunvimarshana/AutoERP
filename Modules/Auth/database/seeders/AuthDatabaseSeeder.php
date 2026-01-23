<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Auth Module Database Seeder
 */
class AuthDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds
     *
     * @return void
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
        ]);
    }
}
