<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Auth\Database\Seeders\AuthSeeder;
use Modules\Customer\Database\Seeders\CustomerSeeder;
use Modules\Finance\Database\Seeders\FinancePaymentPostingSeeder;
use Modules\Finance\Database\Seeders\FinanceSeeder;
use Modules\Hr\Database\Seeders\HrSeeder;
use Modules\Item\Database\Seeders\ItemSeeder;
use Modules\OrganizationUnit\Database\Seeders\OrganizationUnitSeeder;
use Modules\Payment\Database\Seeders\PaymentSeeder;
use Modules\ReferenceData\Database\Seeders\ReferenceDataSeeder;
use Modules\Sequence\Database\Seeders\SequenceSeeder;
use Modules\Supplier\Database\Seeders\SupplierSeeder;
use Modules\Tenant\Database\Seeders\TenantReferenceAssignmentSeeder;
use Modules\Tenant\Database\Seeders\TenantSeeder;
use Modules\UOM\Database\Seeders\UomSeeder;
use Modules\User\Database\Seeders\PlatformOperatorSeeder;
use Modules\User\Database\Seeders\TenantPermissionSeeder;
use Modules\Vehicle\Database\Seeders\VehicleSeeder;
use Modules\Warehouse\Database\Seeders\WarehouseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app(TenantExecutionContextInterface::class)->runAsControlPlane(function (): void {
            $this->call([
                TenantSeeder::class,
                OrganizationUnitSeeder::class,
                PlatformOperatorSeeder::class,
                AuthSeeder::class,
                ReferenceDataSeeder::class,
                TenantReferenceAssignmentSeeder::class,
                SequenceSeeder::class,
                UomSeeder::class,
                WarehouseSeeder::class,
                FinanceSeeder::class,
                FinancePaymentPostingSeeder::class,
                PaymentSeeder::class,
                ItemSeeder::class,
                SupplierSeeder::class,
                CustomerSeeder::class,
                VehicleSeeder::class,
                HrSeeder::class,
                TenantPermissionSeeder::class,
            ]);
        });
    }
}
