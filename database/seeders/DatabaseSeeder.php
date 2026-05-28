<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\PurchaseModuleSeeder;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\VehicleRentalModuleSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if ((bool) env('SEED_PURCHASE_MODULE', false) && class_exists(PurchaseModuleSeeder::class)) {
            $this->call([
                PurchaseModuleSeeder::class,
            ]);
        }

        if ((bool) env('SEED_VEHICLE_RENTAL_MODULE', false) && class_exists(VehicleRentalModuleSeeder::class)) {
            $this->call([
                VehicleRentalModuleSeeder::class,
            ]);
        }
    }
}
