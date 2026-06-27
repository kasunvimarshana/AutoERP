<?php

declare(strict_types=1);

namespace Modules\Vehicle\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\Vehicle\Models\VehicleMake;
use Modules\Vehicle\Models\VehicleModel;
use Modules\Vehicle\Models\VehicleType;

final class VehicleSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $tenantId = (int) $tenant->getKey();
            $organizationUnitId = $organizationUnit?->getKey();
            $makes = $this->seedMakesAndModels($tenantId, $organizationUnitId);
            $type = $this->seedType($tenantId, $organizationUnitId);
            $category = $this->seedCategory($tenantId, $organizationUnitId);

            Vehicle::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'vehicle_number' => 'VEH-000001'],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'code' => 'VEH-DEMO',
                    'vehicle_make_id' => $makes['TOYOTA']['make']->getKey(),
                    'vehicle_model_id' => $makes['TOYOTA']['model']->getKey(),
                    'vehicle_type_id' => $type->getKey(),
                    'vehicle_category_id' => $category->getKey(),
                    'registration_number' => 'AUTOERP-001',
                    'manufacture_year' => 2020,
                    'color' => 'Silver',
                    'fuel_type' => 'petrol',
                    'transmission_type' => 'automatic',
                    'odometer_reading' => '0.000000',
                    'odometer_unit' => 'km',
                    'status' => 'active',
                    'notes' => 'Default vehicle for local development and testing.',
                    'metadata' => ['seed_source' => 'vehicle_module'],
                ],
            );
        }, 3);
    }


    /**
     * @return array<string,array{make:VehicleMake,model:VehicleModel}>
     */
    private function seedMakesAndModels(int $tenantId, ?int $organizationUnitId): array
    {
        $definitions = [
            'TOYOTA' => ['Toyota', 'COROLLA', 'Corolla'],
            'HONDA' => ['Honda', 'CIVIC', 'Civic'],
        ];

        $records = [];
        foreach ($definitions as $makeCode => [$makeName, $modelCode, $modelName]) {
            $make = VehicleMake::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $makeCode],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $makeName,
                    'description' => 'Default vehicle make.',
                    'is_active' => true,
                ],
            );
            $model = VehicleModel::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'vehicle_make_id' => $make->getKey(), 'code' => $modelCode],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $modelName,
                    'description' => 'Default vehicle model.',
                    'is_active' => true,
                ],
            );
            $records[$makeCode] = ['make' => $make, 'model' => $model];
        }

        return $records;
    }

    private function seedType(int $tenantId, ?int $organizationUnitId): VehicleType
    {
        foreach (['CAR' => 'Car', 'VAN' => 'Van'] as $code => $name) {
            $type = VehicleType::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $name,
                    'description' => 'Default vehicle type.',
                    'is_active' => true,
                    'sort_order' => $code === 'CAR' ? 1 : 2,
                ],
            );
            if ($code === 'CAR') {
                $default = $type;
            }
        }

        return $default;
    }

    private function seedCategory(int $tenantId, ?int $organizationUnitId): VehicleCategory
    {
        foreach (['CUSTOMER' => 'Customer Vehicles', 'FLEET' => 'Fleet Vehicles'] as $code => $name) {
            $category = VehicleCategory::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => $code],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'parent_id' => null,
                    'name' => $name,
                    'description' => 'Default vehicle category.',
                    'is_active' => true,
                    'sort_order' => $code === 'CUSTOMER' ? 1 : 2,
                ],
            );
            if ($code === 'CUSTOMER') {
                $default = $category;
            }
        }

        return $default;
    }
}
