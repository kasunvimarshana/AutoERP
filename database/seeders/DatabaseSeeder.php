<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Auth\Infrastructure\Persistence\Eloquent\Seeders\AuthModuleSeeder;
use Modules\Core\Infrastructure\Persistence\Eloquent\Seeders\CoreBootstrapSeeder;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Seeders\CustomerModuleSeeder;
use Modules\Document\Infrastructure\Persistence\Eloquent\Seeders\DocumentModuleSeeder;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Seeders\FinanceModuleSeeder;
use Modules\HR\Infrastructure\Persistence\Eloquent\Seeders\HRModuleSeeder;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Seeders\InventoryModuleSeeder;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Seeders\InvoiceModuleSeeder;
use Modules\Item\Infrastructure\Persistence\Eloquent\Seeders\ItemModuleSeeder;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Seeders\PaymentModuleSeeder;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Seeders\PricingModuleSeeder;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Seeders\PurchaseModuleSeeder;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Seeders\SalesModuleSeeder;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Seeders\SupplierModuleSeeder;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Seeders\UomModuleSeeder;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Seeders\VehicleModuleSeeder;
use Modules\VehicleRental\Infrastructure\Persistence\Eloquent\Seeders\VehicleRentalModuleSeeder;
use Modules\VehicleService\Infrastructure\Persistence\Eloquent\Seeders\VehicleServiceModuleSeeder;
use Modules\Voucher\Infrastructure\Persistence\Eloquent\Seeders\VoucherModuleSeeder;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Seeders\WarehouseModuleSeeder;

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
            class_exists(FinanceModuleSeeder::class) ? FinanceModuleSeeder::class : null,
            class_exists(WarehouseModuleSeeder::class) ? WarehouseModuleSeeder::class : null,
            class_exists(ItemModuleSeeder::class) ? ItemModuleSeeder::class : null,
            class_exists(CustomerModuleSeeder::class) ? CustomerModuleSeeder::class : null,
            class_exists(SupplierModuleSeeder::class) ? SupplierModuleSeeder::class : null,
            class_exists(HRModuleSeeder::class) ? HRModuleSeeder::class : null,
            class_exists(VehicleModuleSeeder::class) ? VehicleModuleSeeder::class : null,
            class_exists(InventoryModuleSeeder::class) ? InventoryModuleSeeder::class : null,
            class_exists(PricingModuleSeeder::class) ? PricingModuleSeeder::class : null,
            class_exists(PaymentModuleSeeder::class) ? PaymentModuleSeeder::class : null,
            class_exists(DocumentModuleSeeder::class) ? DocumentModuleSeeder::class : null,
            class_exists(PurchaseModuleSeeder::class) ? PurchaseModuleSeeder::class : null,
            class_exists(SalesModuleSeeder::class) ? SalesModuleSeeder::class : null,
            class_exists(VehicleServiceModuleSeeder::class) ? VehicleServiceModuleSeeder::class : null,
            class_exists(VehicleRentalModuleSeeder::class) ? VehicleRentalModuleSeeder::class : null,
            class_exists(VoucherModuleSeeder::class) ? VoucherModuleSeeder::class : null,
            class_exists(InvoiceModuleSeeder::class) ? InvoiceModuleSeeder::class : null,
        ]));

        if ($this->shouldSeedScenarioData()) {
            $this->call([
                AutoErpScenarioSeeder::class,
            ]);
        }
    }

    private function shouldSeedScenarioData(): bool
    {
        $configured = env('SEED_AUTOERP_SCENARIOS');
        if ($configured !== null) {
            return filter_var($configured, FILTER_VALIDATE_BOOL);
        }

        return app()->environment(['local', 'testing']);
    }
}
