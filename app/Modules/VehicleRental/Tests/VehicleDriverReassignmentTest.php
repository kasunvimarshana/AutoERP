<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\HR\Models\HrEmployee;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\ReassignmentType;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\VehicleRentalReassignment;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\VehicleDriverReassignmentService;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class VehicleDriverReassignmentTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_driver_reassignment_on_running_chart_records_immutable_audit_log(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $driver1Id = DB::table('hr_employees')->insertGetId([
                'tenant_id' => $context->tenantId,
                'employee_number' => 'DRV-001',
                'first_name' => 'John',
                'display_name' => 'John Doe',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $driver2Id = DB::table('hr_employees')->insertGetId([
                'tenant_id' => $context->tenantId,
                'employee_number' => 'DRV-002',
                'first_name' => 'Jane',
                'display_name' => 'Jane Smith',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, $customerAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-07T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Handover',
            ]);

            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-REASSIGN-01',
                'starts_at' => '2026-09-08T08:00:00+00:00',
                'ends_at' => '2026-09-08T18:00:00+00:00',
                'start_odometer' => '10000.000000',
                'end_odometer' => '10200.000000',
                'driver_identity_source' => 'employee',
                'driver_employee_id' => $driver1Id,
                'driver_name_snapshot' => 'John Doe',
            ]);

            $reassignmentService = app(VehicleDriverReassignmentService::class);
            $reassignment = $reassignmentService->reassignRunningChartDriver(
                $context,
                $chart->id,
                $driver2Id,
                'Driver sick, replaced by standby driver',
                (int) $chart->row_version
            );

            $this->assertSame(ReassignmentType::DriverReassignment, $reassignment->reassignment_type);
            $this->assertSame($driver1Id, (int) $reassignment->previous_driver_employee_id);
            $this->assertSame($driver2Id, (int) $reassignment->new_driver_employee_id);
            $this->assertSame('Driver sick, replaced by standby driver', $reassignment->reason);

            $chart->refresh();
            $this->assertSame($driver2Id, (int) $chart->driver_employee_id);

            // Verify in database audit table
            $exists = VehicleRentalReassignment::query()
                ->where('running_chart_id', $chart->id)
                ->where('new_driver_employee_id', $driver2Id)
                ->exists();
            $this->assertTrue($exists);
        });
    }

    public function test_driver_reassignment_rejects_driver_booked_on_overlapping_trip(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $driverId = DB::table('hr_employees')->insertGetId([
                'tenant_id' => $context->tenantId,
                'employee_number' => 'DRV-BUSY',
                'first_name' => 'Busy',
                'display_name' => 'Busy Driver',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, $customerAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-07T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Handover',
            ]);

            $charts = app(RunningChartService::class);
            // Chart 1 already has this driver
            $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-CHART-1',
                'starts_at' => '2026-09-08T08:00:00+00:00',
                'ends_at' => '2026-09-08T18:00:00+00:00',
                'driver_identity_source' => 'employee',
                'driver_employee_id' => $driverId,
            ]);

            // Chart 2 with overlapping time
            $chart2 = $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-CHART-2',
                'starts_at' => '2026-09-08T10:00:00+00:00',
                'ends_at' => '2026-09-08T16:00:00+00:00',
            ]);

            $reassignmentService = app(VehicleDriverReassignmentService::class);

            $this->expectException(ValidationException::class);
            $reassignmentService->reassignRunningChartDriver(
                $context,
                $chart2->id,
                $driverId,
                'Reassign to busy driver',
                (int) $chart2->row_version
            );
        });
    }

    public function test_mid_period_vehicle_replacement_returns_previous_use_and_rebalances_owner_rent(): void
    {
        [$context, $customerData, $ownerData] = $this->fixture();
        $this->grant($context, array_keys(RentalAuthorization::descriptions()));

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = ['base_rate' => '30000.000000'];
            $agreements = app(AgreementService::class);
            $customerAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customerAg = $agreements->change(AgreementKind::Customer, $context, $customerAg->id, $customerAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '25000.000000'];
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $vehicle2Id = DB::table('vehicles')->insertGetId([
                'tenant_id' => $context->tenantId,
                'vehicle_number' => 'REPLACE-VEH-02',
                'registration_number' => 'WP-CAD-5566',
                'status' => 'active',
            ]);

            $ownerData2 = array_replace($ownerData, [
                'reference' => 'AG-OWNER-2',
                'vehicle_id' => $vehicle2Id,
            ]);
            $ownerAg2 = $agreements->create(AgreementKind::Owner, $context, $ownerData2);
            $ownerAg2 = $agreements->change(AgreementKind::Owner, $context, $ownerAg2->id, $ownerAg2->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $customerAg->id, $customerAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-01T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+00:00',
                'odometer' => '5000.000000',
                'reason' => 'Handover',
            ]);

            $reassignmentService = app(VehicleDriverReassignmentService::class);
            $reassignment = $reassignmentService->replaceVehicle($context, $use->id, [
                'cutover_at' => '2026-09-15T12:00:00+00:00',
                'return_odometer' => '6500.000000',
                'new_vehicle_id' => $vehicle2Id,
                'new_owner_agreement_id' => $ownerAg2->id,
                'new_handover_odometer' => '1000.000000',
                'reason' => 'Engine breakdown on highway',
            ], (int) $use->row_version);

            $this->assertSame(ReassignmentType::VehicleReplacement, $reassignment->reassignment_type);
            $this->assertSame($ownerData['vehicle_id'], (int) $reassignment->previous_vehicle_id);
            $this->assertSame($vehicle2Id, (int) $reassignment->new_vehicle_id);

            // Previous use is now Returned
            $use->refresh();
            $this->assertSame(VehicleUseStatus::Returned, $use->status);
            $this->assertEqualsWithDelta(6500.0, (float) $use->return_odometer, 0.01);

            // Financial rebalancing created an unearned rent adjustment for lessor
            $adj = AgreementAdjustment::query()->where('owner_agreement_id', $ownerAg->id)->first();
            $this->assertNotNull($adj);
            $this->assertSame('credit_note', $adj->adjustment_type->value);
            $this->assertGreaterThan(0, (float) $adj->amount);
        });
    }
}
