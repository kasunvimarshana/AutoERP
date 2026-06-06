<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Auth\Database\Seeders\AuthSeeder;
use Modules\Core\Database\Seeders\CoreSeeder;
use Modules\Item\Database\Seeders\ItemSeeder;
use Modules\UOM\Database\Seeders\UomSeeder;
use Modules\Warehouse\Database\Seeders\WarehouseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CoreSeeder::class,
            AuthSeeder::class,
            UomSeeder::class,
            ItemSeeder::class,
            WarehouseSeeder::class,
        ]);
    }
}
