<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Seeders\AuthModuleSeeder;
use Modules\Core\Infrastructure\Persistence\Eloquent\Seeders\CoreBootstrapSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CoreBootstrapSeeder::class,
            AuthModuleSeeder::class,
        ]);
    }
}
