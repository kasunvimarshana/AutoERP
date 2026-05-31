<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Seeders\AuthModuleSeeder;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\DocumentModuleSeeder;
use Modules\Item\Infrastructure\Persistence\Eloquent\Seeders\ItemModuleSeeder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\PurchaseModuleSeeder;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\VehicleRentalModuleSeeder;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\VoucherModuleSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(array_filter([
            class_exists(AuthModuleSeeder::class) ? AuthModuleSeeder::class : null,
            class_exists(ItemModuleSeeder::class) ? ItemModuleSeeder::class : null,
            class_exists(DocumentModuleSeeder::class) ? DocumentModuleSeeder::class : null,
        ]));

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

        if ((bool) env('SEED_VOUCHER_MODULE', false) && class_exists(VoucherModuleSeeder::class)) {
            $this->call([
                VoucherModuleSeeder::class,
            ]);
        }
    }
}
