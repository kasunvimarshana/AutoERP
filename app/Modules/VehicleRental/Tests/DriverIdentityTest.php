<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\DriverIdentitySource;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Tests\TestCase;

final class DriverIdentityTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-10T12:00:00+00:00'));
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
        });
    }

    public function test_employee_driver_is_tenant_scoped_and_snapshotted(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $employee = DB::table('hr_employees')->insertGetId([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'employee_number' => 'DRV-001',
                'display_name' => 'Rental Driver One',
                'first_name' => 'Rental',
                'status' => 'active',
                'availability_status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $chart = app(RunningChartService::class)->create($context, $use->id, $use->row_version, $facts + [
                'driver_identity_source' => DriverIdentitySource::Employee->value,
                'driver_employee_id' => $employee,
                'driver_name_snapshot' => 'Client must not control this snapshot',
                'driver_reference_snapshot' => 'WRONG',
            ]);

            self::assertSame(DriverIdentitySource::Employee, $chart->driver_identity_source);
            self::assertSame($employee, $chart->driver_employee_id);
            self::assertSame('Rental Driver One', $chart->driver_name_snapshot);
            self::assertSame('DRV-001', $chart->driver_reference_snapshot);
        });
    }

    public function test_external_driver_requires_and_normalizes_stable_reference(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $chart = app(RunningChartService::class)->create($context, $use->id, $use->row_version, $facts + [
                'driver_identity_source' => DriverIdentitySource::External->value,
                'driver_name_snapshot' => '  External Driver  ',
                'driver_reference_snapshot' => ' ext-001 ',
            ]);

            self::assertSame('External Driver', $chart->driver_name_snapshot);
            self::assertSame('EXT-001', $chart->driver_reference_snapshot);
            self::assertNull($chart->driver_employee_id);
        });
    }

    public function test_foreign_tenant_employee_cannot_be_used_as_driver(): void
    {
        [$foreignContext] = $this->fixture();
        $foreignEmployee = $this->withTenantExecutionContext($foreignContext->tenantId, fn () => DB::table('hr_employees')->insertGetId([
            'tenant_id' => $foreignContext->tenantId,
            'organization_unit_id' => $foreignContext->organizationUnitId,
            'employee_number' => 'FOREIGN-DRV',
            'display_name' => 'Foreign Driver',
            'first_name' => 'Foreign',
            'status' => 'active',
            'availability_status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        $this->custodyFixture(function ($context, $use, $facts) use ($foreignEmployee): void {
            $this->expectException(ValidationException::class);
            app(RunningChartService::class)->create($context, $use->id, $use->row_version, $facts + [
                'driver_identity_source' => DriverIdentitySource::Employee->value,
                'driver_employee_id' => $foreignEmployee,
            ]);
        });
    }

    public function test_finalized_chart_identity_is_immutable(): void
    {
        $this->custodyFixture(function ($context, $use, $facts): void {
            $service = app(RunningChartService::class);
            $chart = $service->create($context, $use->id, $use->row_version, $facts + [
                'driver_identity_source' => DriverIdentitySource::External->value,
                'driver_name_snapshot' => 'External Driver',
                'driver_reference_snapshot' => 'EXT-002',
            ]);
            $chart = $service->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);

            $this->expectException(ValidationException::class);
            $service->change($context, $chart->id, $chart->row_version, RunningChartAction::Update, $facts + [
                'driver_identity_source' => DriverIdentitySource::External->value,
                'driver_name_snapshot' => 'Someone Else',
                'driver_reference_snapshot' => 'EXT-003',
            ]);
        });
    }
}
