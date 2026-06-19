<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Auth\Database\Seeders\AuthSeeder;
use Modules\Configuration\Database\Seeders\ConfigurationSeeder;
use Modules\Core\Database\Seeders\CoreSeeder;
use Modules\Customer\Database\Seeders\CustomerSeeder;
use Modules\Finance\Database\Seeders\FinanceSeeder;
use Modules\Hr\Database\Seeders\HrSeeder;
use Modules\Item\Database\Seeders\ItemSeeder;
use Modules\OrganizationUnit\Database\Seeders\OrganizationUnitSeeder;
use Modules\Payment\Database\Seeders\PaymentSeeder;
use Modules\Purchase\Database\Seeders\PurchaseSeeder;
use Modules\Sales\Database\Seeders\SalesSeeder;
use Modules\Sequence\Database\Seeders\SequenceSeeder;
use Modules\Supplier\Database\Seeders\SupplierSeeder;
use Modules\Tenant\Database\Seeders\TenantDomainSeeder;
use Modules\Tenant\Database\Seeders\TenantSeeder;
use Modules\UOM\Database\Seeders\UomSeeder;
use Modules\User\Database\Seeders\UserSeeder;
use Modules\Vehicle\Database\Seeders\VehicleSeeder;
use Modules\VehicleRental\Database\Seeders\VehicleRentalSeeder;
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
            TenantSeeder::class,
            TenantDomainSeeder::class,
            OrganizationUnitSeeder::class,
            UserSeeder::class,
            AuthSeeder::class,
            ConfigurationSeeder::class,
            SequenceSeeder::class,
            UomSeeder::class,
            WarehouseSeeder::class,
            FinanceSeeder::class,
            PaymentSeeder::class,
            ItemSeeder::class,
            SupplierSeeder::class,
            PurchaseSeeder::class,
            CustomerSeeder::class,
            SalesSeeder::class,
            VehicleSeeder::class,
            VehicleRentalSeeder::class,
            HrSeeder::class,
        ]);
    }
}
