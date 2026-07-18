<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\VehicleRental\Services\RentalAvailabilityService;
use Tests\TestCase;

final class RentalAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private const AVAILABILITY_START = '2026-07-20 08:00:00';

    private const AVAILABILITY_END = '2026-07-21 08:00:00';

    public function test_availability_uses_vehicle_owned_tenant_and_organization_unit_scope(): void
    {
        $tenantId = $this->createTenant('RENTAL-AVAILABILITY');
        $currentOrganizationUnitId = $this->createOrganizationUnit($tenantId, 'Main Branch', 'MAIN');
        $otherOrganizationUnitId = $this->createOrganizationUnit($tenantId, 'Other Branch', 'OTHER');
        $otherTenantId = $this->createTenant('OTHER-TENANT');

        $tenantVehicleId = $this->createVehicle($tenantId, null, 'TENANT-VEHICLE');
        $currentUnitVehicleId = $this->createVehicle($tenantId, $currentOrganizationUnitId, 'CURRENT-UNIT-VEHICLE');
        $otherUnitVehicleId = $this->createVehicle($tenantId, $otherOrganizationUnitId, 'OTHER-UNIT-VEHICLE');
        $otherTenantVehicleId = $this->createVehicle($otherTenantId, null, 'OTHER-TENANT-VEHICLE');

        $service = app(RentalAvailabilityService::class);
        $availableVehicleIds = $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => $service->queryAvailable(
                $tenantId,
                $currentOrganizationUnitId,
                self::AVAILABILITY_START,
                self::AVAILABILITY_END,
            )->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(),
        );

        self::assertContains($tenantVehicleId, $availableVehicleIds);
        self::assertContains($currentUnitVehicleId, $availableVehicleIds);
        self::assertNotContains($otherUnitVehicleId, $availableVehicleIds);
        self::assertNotContains($otherTenantVehicleId, $availableVehicleIds);
        self::assertSame(
            $tenantVehicleId,
            $this->withTenantExecutionContext(
                $tenantId,
                fn (): int => (int) $service->assertVehicle(
                    $tenantId,
                    $currentOrganizationUnitId,
                    $tenantVehicleId,
                    self::AVAILABILITY_START,
                    self::AVAILABILITY_END,
                )->getKey(),
            ),
        );
    }

    public function test_availability_search_supports_every_vehicle_identifier_owned_by_the_vehicle_module(): void
    {
        $tenantId = $this->createTenant('RENTAL-SEARCH');
        $organizationUnitId = $this->createOrganizationUnit($tenantId, 'Main Branch', 'MAIN');
        $vehicleId = $this->createVehicle($tenantId, null, 'SEARCHABLE', [
            'vehicle_number' => 'VEH-SEARCH-001',
            'code' => 'FLEET-CODE-001',
            'registration_number' => 'CAR-1001',
            'chassis_number' => 'CHASSIS-SEARCH-001',
            'engine_number' => 'ENGINE-SEARCH-001',
            'vin_number' => 'VIN-SEARCH-001',
        ]);

        $searchTerms = [
            'VEH-SEARCH-001',
            'FLEET-CODE-001',
            'CAR-1001',
            'CHASSIS-SEARCH-001',
            'ENGINE-SEARCH-001',
            'VIN-SEARCH-001',
        ];
        $service = app(RentalAvailabilityService::class);

        foreach ($searchTerms as $searchTerm) {
            $resultIds = $this->withTenantExecutionContext(
                $tenantId,
                fn (): array => $service->queryAvailable(
                    $tenantId,
                    $organizationUnitId,
                    self::AVAILABILITY_START,
                    self::AVAILABILITY_END,
                    search: $searchTerm,
                )->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all(),
            );

            self::assertSame([$vehicleId], $resultIds, "Vehicle search failed for {$searchTerm}.");
        }
    }

    private function createTenant(string $code): int
    {
        $currencyCode = strtoupper(substr($code, 0, 3));
        $currencyId = (int) DB::table('currencies')->insertGetId([
            'code' => $currencyCode,
            'name' => $code.' Currency',
            'symbol' => $currencyCode,
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => Str::lower($code),
            'base_currency_id' => $currencyId,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrganizationUnit(int $tenantId, string $name, string $code): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => $code,
            'path' => '/'.Str::lower($code),
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createVehicle(
        int $tenantId,
        ?int $organizationUnitId,
        string $identifier,
        array $overrides = [],
    ): int {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_number' => 'VEH-'.$identifier,
            'code' => 'CODE-'.$identifier,
            'registration_number' => 'REG-'.$identifier,
            'status' => VehicleStatus::Active->value,
            'created_at' => now(),
            'updated_at' => now(),
            ...$overrides,
        ]);
    }
}
