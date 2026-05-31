<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class VehicleModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        DB::transaction(function (): void {
            $tenantId = $this->tenantId();
            $organizationUnitId = $this->organizationUnitId($tenantId);

            $this->seedVehicleWithOwnership($tenantId, $organizationUnitId, [
                'vehicle_code' => 'VEH-DEMO-001',
                'license_plate' => 'WP DEMO-001',
                'vin' => 'DEMO-VIN-001',
                'make' => 'Toyota',
                'model' => 'HiAce',
                'year' => 2022,
                'color' => 'White',
                'category' => 'Van',
                'usage_profile' => 'dual',
                'service_enabled' => true,
                'rental_enabled' => true,
                'fuel_type' => 'Diesel',
                'transmission' => 'Automatic',
                'seating_capacity' => 12,
                'current_odometer' => 38450,
                'status' => 'active',
                'registration_expiry' => '2026-12-31',
                'insurance_expiry' => '2026-12-31',
            ], [
                'ownership_type' => 'own',
                'owner_type' => 'company',
                'owner_name' => 'Internal Company',
                'ownership_role' => 'legal_owner',
                'start_date' => '2026-01-01',
                'notes' => 'Company fleet vehicle for service and rental testing.',
            ]);

            $this->seedVehicleWithOwnership($tenantId, $organizationUnitId, [
                'vehicle_code' => 'VEH-DEMO-002',
                'license_plate' => 'WP DEMO-002',
                'vin' => 'DEMO-VIN-002',
                'make' => 'Nissan',
                'model' => 'Caravan',
                'year' => 2021,
                'color' => 'Silver',
                'category' => 'Van',
                'usage_profile' => 'rent_only',
                'service_enabled' => true,
                'rental_enabled' => true,
                'fuel_type' => 'Diesel',
                'transmission' => 'Manual',
                'seating_capacity' => 10,
                'current_odometer' => 52900,
                'status' => 'active',
                'registration_expiry' => '2026-10-30',
                'insurance_expiry' => '2026-10-30',
            ], [
                'ownership_type' => 'provider',
                'owner_type' => 'external_party',
                'owner_name' => 'External Fleet Provider',
                'ownership_role' => 'provider',
                'start_date' => '2026-02-01',
                'notes' => 'Provider-owned vehicle. Rental provider payable is owned by rental workflows.',
            ]);
        }, 3);
    }

    /**
     * @param array<string,mixed> $vehicle
     * @param array<string,mixed> $ownership
     */
    private function seedVehicleWithOwnership(int $tenantId, int $organizationUnitId, array $vehicle, array $ownership): void
    {
        DB::table('vehicles')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'vehicle_code' => $vehicle['vehicle_code'],
            ],
            [
                ...$vehicle,
                'metadata' => json_encode(['seed_source' => 'vehicle_module'], JSON_THROW_ON_ERROR),
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'tenant_id' => $tenantId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $vehicleId = $this->requiredIdBy('vehicles', [
            'tenant_id' => $tenantId,
            'vehicle_code' => $vehicle['vehicle_code'],
        ]);

        if (! Schema::hasTable('vehicle_ownerships')) {
            return;
        }

        DB::table('vehicle_ownerships')->updateOrInsert(
            [
                'tenant_id' => $tenantId,
                'vehicle_id' => $vehicleId,
                'ownership_role' => $ownership['ownership_role'],
                'start_date' => $ownership['start_date'],
            ],
            [
                ...$ownership,
                'is_current' => true,
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'tenant_id' => $tenantId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function tenantId(): int
    {
        $id = DB::table('tenants')->where('code', strtoupper((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')))->value('id');

        if ($id === null) {
            throw new RuntimeException('Default tenant must be seeded before vehicle module data.');
        }

        return (int) $id;
    }

    private function organizationUnitId(int $tenantId): int
    {
        $id = DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', strtoupper((string) env('AUTH_LOCAL_ORGANIZATION_UNIT_CODE', 'MAIN')))
            ->value('id');

        if ($id === null) {
            throw new RuntimeException('Default organization unit must be seeded before vehicle module data.');
        }

        return (int) $id;
    }

    /**
     * @param array<string,mixed> $criteria
     */
    private function requiredIdBy(string $table, array $criteria): int
    {
        $query = DB::table($table);
        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        $id = $query->value('id');
        if ($id === null) {
            throw new RuntimeException('Failed to resolve seeded id from [' . $table . '].');
        }

        return (int) $id;
    }
}
