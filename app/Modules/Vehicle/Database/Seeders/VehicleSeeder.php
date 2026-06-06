<?php

declare(strict_types=1);

namespace Modules\Vehicle\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('vehicle_makes')) {
            return;
        }

        $tenantId = $this->defaultTenantId();
        $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);

        foreach ([
            ['code' => 'TOYOTA', 'name' => 'Toyota'],
            ['code' => 'NISSAN', 'name' => 'Nissan'],
            ['code' => 'HONDA', 'name' => 'Honda'],
        ] as $make) {
            DB::table('vehicle_makes')->updateOrInsert(
                ['tenant_id' => $tenantId, 'code' => $make['code']],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $make['name'],
                    'description' => 'Seed vehicle make.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $toyotaId = DB::table('vehicle_makes')->where('tenant_id', $tenantId)->where('code', 'TOYOTA')->value('id');
        if ($toyotaId !== null) {
            foreach ([
                ['code' => 'AQUA', 'name' => 'Aqua'],
                ['code' => 'COROLLA', 'name' => 'Corolla'],
            ] as $model) {
                DB::table('vehicle_models')->updateOrInsert(
                    ['tenant_id' => $tenantId, 'vehicle_make_id' => $toyotaId, 'code' => $model['code']],
                    [
                        'organization_unit_id' => $organizationUnitId,
                        'name' => $model['name'],
                        'year_from' => null,
                        'year_to' => null,
                        'description' => 'Seed vehicle model.',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        foreach ([
            ['code' => 'CAR', 'name' => 'Car'],
            ['code' => 'VAN', 'name' => 'Van'],
            ['code' => 'TRUCK', 'name' => 'Truck'],
        ] as $index => $type) {
            DB::table('vehicle_types')->updateOrInsert(
                ['tenant_id' => $tenantId, 'code' => $type['code']],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'name' => $type['name'],
                    'description' => 'Seed vehicle type.',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        foreach ([
            ['code' => 'GENERAL', 'name' => 'General Vehicles'],
            ['code' => 'SERVICE', 'name' => 'Service Vehicles'],
            ['code' => 'RENTAL', 'name' => 'Rental Vehicles'],
        ] as $index => $category) {
            DB::table('vehicle_categories')->updateOrInsert(
                ['tenant_id' => $tenantId, 'code' => $category['code']],
                [
                    'organization_unit_id' => $organizationUnitId,
                    'parent_id' => null,
                    'name' => $category['name'],
                    'description' => 'Seed vehicle category.',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function defaultTenantId(): int
    {
        $code = strtoupper(trim((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')));
        $id = DB::table('tenants')->where('code', $code)->value('id')
            ?? DB::table('tenants')->orderBy('id')->value('id');

        if ($id === null) {
            throw new RuntimeException('Seed a tenant before running the Vehicle module seeder.');
        }

        return (int) $id;
    }

    private function defaultOrganizationUnitId(int $tenantId): ?int
    {
        return DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->value('id');
    }
}
