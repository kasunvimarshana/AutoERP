<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed test data only in local/testing environments.
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call([
            // OrderSeeder::class,
        ]);
    }
}
