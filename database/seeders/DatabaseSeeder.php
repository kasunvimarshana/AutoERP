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
use Modules\User\Models\PermissionModel;
use Modules\User\Models\RoleModel;
use Modules\User\Models\RolePermissionModel;
use Modules\Reporting\Database\Seeders\ReportingSeeder;

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
            ReportingSeeder::class,
        ]);

        //
        $superAdmin = RoleModel::query()
            ->where('name', 'Super Admin')
            ->firstOrFail();

        $rows = PermissionModel::query()
            ->where('tenant_id', $superAdmin->tenant_id)
            ->pluck('id')
            ->map(fn (int $permissionId): array => [
                'tenant_id' => $superAdmin->tenant_id,
                'role_id' => $superAdmin->id,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        RolePermissionModel::query()->insertOrIgnore($rows);
        //
    }
}
