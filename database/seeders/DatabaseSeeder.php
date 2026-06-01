<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Seeders\AuthModuleSeeder;
use Modules\Core\Infrastructure\Persistence\Eloquent\Seeders\CoreBootstrapSeeder;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\DocumentModuleSeeder;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Seeders\FinanceModuleSeeder;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Seeders\InventoryModuleSeeder;
use Modules\Item\Infrastructure\Persistence\Eloquent\Seeders\ItemModuleSeeder;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Seeders\PaymentModuleSeeder;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Seeders\PricingModuleSeeder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\PurchaseModuleSeeder;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\SalesModuleSeeder;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Seeders\UomModuleSeeder;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Seeders\VehicleModuleSeeder;
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
            class_exists(CoreBootstrapSeeder::class) ? CoreBootstrapSeeder::class : null,
            class_exists(AuthModuleSeeder::class) ? AuthModuleSeeder::class : null,
            class_exists(UomModuleSeeder::class) ? UomModuleSeeder::class : null,
            class_exists(ItemModuleSeeder::class) ? ItemModuleSeeder::class : null,
            class_exists(InventoryModuleSeeder::class) ? InventoryModuleSeeder::class : null,
            class_exists(FinanceModuleSeeder::class) ? FinanceModuleSeeder::class : null,
            class_exists(PricingModuleSeeder::class) ? PricingModuleSeeder::class : null,
            class_exists(PaymentModuleSeeder::class) ? PaymentModuleSeeder::class : null,
            class_exists(DocumentModuleSeeder::class) ? DocumentModuleSeeder::class : null,
            class_exists(VehicleModuleSeeder::class) ? VehicleModuleSeeder::class : null,
        ]));

        if ((bool) env('SEED_PURCHASE_MODULE', false) && class_exists(PurchaseModuleSeeder::class)) {
            $this->call([
                PurchaseModuleSeeder::class,
            ]);
        }

        if ((bool) env('SEED_SALES_MODULE', false) && class_exists(SalesModuleSeeder::class)) {
            $this->call([
                SalesModuleSeeder::class,
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
