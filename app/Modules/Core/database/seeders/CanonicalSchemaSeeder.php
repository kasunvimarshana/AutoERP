<?php

namespace App\Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Finance\Database\Seeders\CanonicalDocumentTypesSeeder;
use App\Modules\Core\Database\Seeders\CanonicalPermissionsSeeder;
use App\Modules\Product\Database\Seeders\CanonicalUomsSeeder;
use App\Modules\Inventory\Database\Seeders\CanonicalInventoryAdjustmentReasonsSeeder;

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
