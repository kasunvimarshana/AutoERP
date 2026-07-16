<?php

declare(strict_types=1);

namespace Tests\Feature\VehicleRental;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\VehicleRental\Services\RentalAvailabilityService;
use Tests\TestCase;

final class RentalVehicleServiceAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private const AVAILABILITY_START = '2026-07-20 08:00:00';

    private const AVAILABILITY_END = '2026-07-20 18:00:00';

    public function test_under_service_vehicle_cannot_be_selected_for_rental_allocation(): void
    {
        $tenantId = $this->createTenant();
        $vehicleId = $this->createVehicle($tenantId, VehicleStatus::UnderService, 'SERVICE-001');

        $this->expectException(ModelNotFoundException::class);

        app(RentalAvailabilityService::class)->assertVehicle(
            $tenantId,
            null,
            $vehicleId,
            self::AVAILABILITY_START,
            self::AVAILABILITY_END,
        );
    }

    public function test_availability_query_excludes_under_service_vehicle_and_keeps_active_vehicle(): void
    {
        $tenantId = $this->createTenant();
        $activeVehicleId = $this->createVehicle($tenantId, VehicleStatus::Active, 'ACTIVE-001');
        $serviceVehicleId = $this->createVehicle($tenantId, VehicleStatus::UnderService, 'SERVICE-002');

        $availableVehicleIds = app(RentalAvailabilityService::class)
            ->queryAvailable(
                $tenantId,
                null,
                self::AVAILABILITY_START,
                self::AVAILABILITY_END,
            )
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        self::assertContains($activeVehicleId, $availableVehicleIds);
        self::assertNotContains($serviceVehicleId, $availableVehicleIds);
    }

    private function createTenant(): int
    {
        $currencyId = (int) DB::table('currencies')->insertGetId([
            'code' => 'VRA',
            'name' => 'Vehicle Rental Availability Currency',
            'symbol' => 'VRA',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'VR-AVAILABILITY',
            'name' => 'Vehicle Rental Availability Tenant',
            'slug' => 'vehicle-rental-availability-tenant',
            'base_currency_id' => $currencyId,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createVehicle(int $tenantId, VehicleStatus $status, string $suffix): int
    {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_number' => "VEH-{$suffix}",
            'code' => "VEH-{$suffix}",
            'registration_number' => $suffix,
            'status' => $status->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
