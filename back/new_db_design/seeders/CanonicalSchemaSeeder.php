<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CanonicalSchemaSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CanonicalDocumentTypesSeeder::class,
            CanonicalPermissionsSeeder::class,
            CanonicalUomsSeeder::class,
            CanonicalInventoryAdjustmentReasonsSeeder::class,
        ]);
    }
}
