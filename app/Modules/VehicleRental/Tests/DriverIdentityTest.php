<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\DriverIdentitySource;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\VehicleUseService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
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
            $employee = $this->driverEmployee($context->tenantId, $context->organizationUnitId, 'DRV-001', 'Rental Driver One');

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
        $foreignEmployee = $this->withTenantExecutionContext($foreignContext->tenantId, fn () => $this->driverEmployee($foreignContext->tenantId, $foreignContext->organizationUnitId, 'FOREIGN-DRV', 'Foreign Driver'));

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

    public function test_same_employee_driver_cannot_finalize_overlapping_usage_on_two_vehicles(): void
    {
        $this->twoVehicleCustody(function ($context, $firstUse, $secondUse): void {
            $employee = $this->driverEmployee($context->tenantId, $context->organizationUnitId, 'DRV-CONFLICT', 'Shared Driver');
            $service = app(RunningChartService::class);
            $identity = ['driver_identity_source' => DriverIdentitySource::Employee->value, 'driver_employee_id' => $employee];
            $first = $service->create($context, $firstUse->id, $firstUse->row_version, $this->facts('DRIVER-A', '100', '120') + $identity);
            $second = $service->create($context, $secondUse->id, $secondUse->row_version, $this->facts('DRIVER-B', '200', '220') + $identity);
            $service->change($context, $first->id, $first->row_version, RunningChartAction::Finalize);

            $this->expectException(ConflictHttpException::class);
            $service->change($context, $second->id, $second->row_version, RunningChartAction::Finalize);
        });
    }

    public function test_same_external_driver_reference_allows_adjacent_non_overlapping_usage(): void
    {
        $this->twoVehicleCustody(function ($context, $firstUse, $secondUse): void {
            $service = app(RunningChartService::class);
            $identity = ['driver_identity_source' => DriverIdentitySource::External->value, 'driver_name_snapshot' => 'Contract Driver', 'driver_reference_snapshot' => 'EXT-ADJ'];
            $first = $service->create($context, $firstUse->id, $firstUse->row_version, $this->facts('EXT-A', '100', '120', '2026-09-07T09:00:15+05:30', '2026-09-07T13:00:00+05:30') + $identity);
            $second = $service->create($context, $secondUse->id, $secondUse->row_version, $this->facts('EXT-B', '200', '220', '2026-09-07T13:00:00+05:30', '2026-09-07T17:00:30+05:30') + $identity);

            self::assertNotNull($service->change($context, $first->id, $first->row_version, RunningChartAction::Finalize)->finalized_at);
            self::assertNotNull($service->change($context, $second->id, $second->row_version, RunningChartAction::Finalize)->finalized_at);
        });
    }

    private function twoVehicleCustody(callable $work): void
    {
        $this->activeFixture(function ($context, $customer, $owner, $input) use ($work): void {
            $uses = app(VehicleUseService::class);
            $firstUse = $uses->plan($context, $customer->id, $customer->row_version, $input);
            $firstUse = $uses->transition($context, $firstUse->id, $firstUse->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['starts_at'], 'odometer' => '100', 'reason' => 'First vehicle collected']);

            $vehicle = DB::table('vehicles')->insertGetId([
                'tenant_id' => $context->tenantId,
                'vehicle_number' => 'DRV-VEH-2',
                'registration_number' => 'DRV-VEH-2',
                'status' => 'active',
            ]);
            $agreements = app(AgreementService::class);
            $secondOwner = $agreements->create(AgreementKind::Owner, $context, [
                'reference' => $owner->reference.'-2',
                'party_id' => $owner->supplier_id,
                'currency_id' => $owner->currency_id,
                'vehicle_id' => $vehicle,
                'agreed_on' => '2026-09-01',
                'starts_on' => '2026-09-07',
                'ends_on' => null,
                'basis' => $owner->basis->value,
                'driver_mode' => $owner->driver_mode->value,
                'terms' => $owner->terms,
            ]);
            $secondOwner = $agreements->change(AgreementKind::Owner, $context, $secondOwner->id, $secondOwner->row_version, AgreementAction::Activate);
            $secondUse = $uses->plan($context, $customer->id, $customer->row_version, array_replace($input, ['vehicle_id' => $vehicle, 'owner_agreement_id' => $secondOwner->id]));
            $secondUse = $uses->transition($context, $secondUse->id, $secondUse->row_version, VehicleUseAction::Handover, ['occurred_at' => $input['starts_at'], 'odometer' => '200', 'reason' => 'Second vehicle collected']);

            $work($context, $firstUse, $secondUse);
        });
    }

    private function driverEmployee(int $tenantId, int $organizationUnitId, string $number, string $name): int
    {
        return DB::table('hr_employees')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'employee_number' => $number,
            'display_name' => $name,
            'first_name' => $name,
            'status' => 'active',
            'availability_status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function facts(string $reference, string $startKm, string $endKm, string $startsAt = '2026-09-07T09:00:15+05:30', string $endsAt = '2026-09-07T17:00:30+05:30'): array
    {
        return ['reference' => $reference, 'starts_at' => $startsAt, 'ends_at' => $endsAt, 'start_odometer' => $startKm, 'end_odometer' => $endKm, 'garage_km' => '0'];
    }
}
