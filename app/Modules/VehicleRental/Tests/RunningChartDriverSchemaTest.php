<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\VehicleRental\Enums\DriverIdentitySource;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Tests\TestCase;

final class RunningChartDriverSchemaTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
        });
    }

    public function test_fresh_schema_persists_driver_identity_and_rejects_cross_tenant_employee_reference(): void
    {
        self::assertTrue(Schema::hasColumns('vehicle_rental_running_charts', [
            'driver_identity_source',
            'driver_employee_id',
            'driver_name_snapshot',
            'driver_reference_snapshot',
        ]));

        [$foreignContext] = $this->fixture();
        $foreignEmployee = DB::table('hr_employees')->insertGetId([
            'tenant_id' => $foreignContext->tenantId,
            'organization_unit_id' => $foreignContext->organizationUnitId,
            'employee_number' => 'FOREIGN-SCHEMA-DRV',
            'display_name' => 'Foreign Schema Driver',
            'first_name' => 'Foreign Schema Driver',
            'status' => 'active',
            'availability_status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->custodyFixture(function ($context, $use, $facts) use ($foreignEmployee): void {
            $chart = app(RunningChartService::class)->create($context, $use->id, $use->row_version, $facts);

            $this->expectException(QueryException::class);
            DB::table('vehicle_rental_running_charts')->where('id', $chart->id)->update([
                'driver_identity_source' => DriverIdentitySource::Employee->value,
                'driver_employee_id' => $foreignEmployee,
                'driver_name_snapshot' => 'Must not persist',
                'driver_reference_snapshot' => 'FOREIGN-SCHEMA-DRV',
            ]);
        });
    }
}
