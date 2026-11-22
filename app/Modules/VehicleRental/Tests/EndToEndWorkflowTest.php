<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Http\Resources\RunningChartRegisterResource;
use Modules\VehicleRental\Http\Resources\VehicleUseResource;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartRegisterService;
use Modules\VehicleRental\Services\RunningChartReportService;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\VehicleDriverReassignmentService;
use Modules\VehicleRental\Services\VehicleUseRegisterService;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\TestCase;

final class EndToEndWorkflowTest extends TestCase
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
            $mock->shouldReceive('assertBilling')->zeroOrMoreTimes();
            $mock->shouldReceive('assertIncident')->zeroOrMoreTimes();
        });
    }

    public function test_plan_company_owned_vehicle_for_internal_use_end_to_end(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context): void {
            // 1. Create a company-owned vehicle
            $companyVehicle = Vehicle::query()->forceCreate([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'vehicle_number' => 'COMP-FLEET-01',
                'registration_number' => 'WP-CAD-9999',
                'status' => VehicleStatus::Active,
            ]);

            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $companyVehicle->id,
                'owner_type' => VehicleOwnerType::Company->value,
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => VehicleOwnershipType::CompanyOwned->value,
                'started_at' => '2026-01-01 00:00:00',
            ]);

            // 2. Plan internal use via planInternal
            $useService = app(VehicleUseService::class);
            $planned = $useService->planInternal($context, [
                'vehicle_id' => $companyVehicle->id,
                'starts_at' => '2026-09-07T08:00:00+05:30',
                'ends_at' => '2026-09-08T18:00:00+05:30',
                'notes' => 'Internal maintenance logistics deployment',
            ]);

            self::assertSame(VehicleUseStatus::Planned, $planned->status);
            self::assertNull($planned->customer_agreement_id);
            self::assertNull($planned->owner_agreement_id);
            self::assertSame($companyVehicle->id, (int) $planned->vehicle_id);

            $useId = (int) $planned->id;
            $useVersion = (int) $planned->row_version;

            // 3. Handover internal company vehicle
            $handedOver = $useService->transition($context, $useId, $useVersion, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T08:30:00+05:30',
                'odometer' => '10000.000',
                'reason' => 'Handed over to internal logistics crew',
            ]);

            self::assertSame(VehicleUseStatus::InCustody, $handedOver->status);
            self::assertSame('10000.000000', $handedOver->handover_odometer);

            // 4. Record a Running Chart for this internal company vehicle
            $chartService = app(RunningChartService::class);
            $chart = $chartService->create($context, $useId, $handedOver->row_version, [
                'reference' => 'RC-INT-001',
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-07T19:00:00+05:30', // 10 hours trip duration
                'start_odometer' => '10000.000',
                'end_odometer' => '10150.000',
                'notes' => 'Site delivery trip',
            ]);

            // Agrees to standard 8h baseline -> 10h trip duration = 2h (120 mins) calculated overtime
            $chartResource = (new \Modules\VehicleRental\Http\Resources\RunningChartResource($chart))->toArray(request());
            self::assertEquals('8.0', $chartResource['agreed_baseline_hours']);
            self::assertSame(120, $chartResource['calculated_ot_minutes']);
            self::assertSame('150.000000', $chartResource['total_km']);

            // Finalize chart
            $chartService->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize);

            // 5. Generate Company Fleet Running Chart report sheet
            $reportService = app(RunningChartReportService::class);
            $report = $reportService->generate($context, [
                'agreement_id' => 0,
                'vehicle_use_id' => $useId,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
            ]);

            self::assertStringContainsString('COMPANY FLEET', $report['header']['report_title']);
            self::assertSame('INTERNAL / COMPANY USE', $report['header']['client_name']);
            self::assertSame('COMPANY OWNED', $report['header']['owner_name']);
            self::assertCount(30, $report['rows']);

            // 6. Return internal company vehicle
            $returned = $useService->transition($context, $useId, $handedOver->row_version, VehicleUseAction::ReturnVehicle, [
                'occurred_at' => '2026-09-08T18:00:00+05:30',
                'odometer' => '10200.000',
                'reason' => 'Completed internal site operations',
            ]);

            self::assertSame(VehicleUseStatus::Returned, $returned->status);
            self::assertSame('10200.000000', $returned->return_odometer);
            self::assertSame(VehicleStatus::Active, $companyVehicle->refresh()->status);
        });
    }

    public function test_replace_vehicle_on_internal_deployment_via_vehicle_use_service(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            // Leased owner vehicle deployed internally
            $agreements = app(AgreementService::class);
            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $useService = app(VehicleUseService::class);
            $initialUse = $useService->planOwner($context, $owner->id, $owner->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'starts_at' => '2026-09-07T08:00:00+05:30',
                'ends_at' => '2026-09-10T18:00:00+05:30',
                'notes' => 'Internal site transport',
            ]);

            $custodyUse = $useService->transition($context, $initialUse->id, $initialUse->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T08:30:00+05:30',
                'odometer' => '25000.000',
                'reason' => 'Handover for deployment',
            ]);

            // Create a company-owned backup vehicle to replace the broken down owner vehicle
            $companyVehicle = Vehicle::query()->forceCreate([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'vehicle_number' => 'BACKUP-01',
                'status' => VehicleStatus::Active,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $companyVehicle->id,
                'owner_type' => VehicleOwnerType::Company->value,
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => VehicleOwnershipType::CompanyOwned->value,
                'started_at' => '2026-01-01 00:00:00',
            ]);

            // Execute replace via VehicleUseService::replace
            $replacementUse = $useService->replace($context, $custodyUse->id, $custodyUse->row_version, [
                'vehicle_id' => $companyVehicle->id,
                'starts_at' => '2026-09-08T10:00:00+05:30',
                'return_odometer' => '25300.000',
                'handover_odometer' => '5000.000',
                'reason' => 'Lessor vehicle engine failure, swapped with company backup vehicle',
            ]);

            self::assertSame(VehicleUseStatus::InCustody, $replacementUse->status);
            self::assertSame($companyVehicle->id, (int) $replacementUse->vehicle_id);
            self::assertSame($custodyUse->id, (int) $replacementUse->replaces_use_id);
            self::assertNull($replacementUse->customer_agreement_id);
            self::assertNull($replacementUse->owner_agreement_id); // Replaced with company vehicle

            // Previous use was transitioned to Returned
            self::assertSame(VehicleUseStatus::Returned, $custodyUse->refresh()->status);
            self::assertSame('25300.000000', $custodyUse->return_odometer);
        });
    }

    public function test_replace_vehicle_on_internal_deployment_via_reassignment_service(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context): void {
            // Company vehicle deployed internally
            $companyVehicle = Vehicle::query()->forceCreate([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'vehicle_number' => 'COMP-02',
                'status' => VehicleStatus::Active,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $companyVehicle->id,
                'owner_type' => VehicleOwnerType::Company->value,
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => VehicleOwnershipType::CompanyOwned->value,
                'started_at' => '2026-01-01 00:00:00',
            ]);

            $useService = app(VehicleUseService::class);
            $initialUse = $useService->planInternal($context, [
                'vehicle_id' => $companyVehicle->id,
                'starts_at' => '2026-09-07T08:00:00+05:30',
                'ends_at' => '2026-09-10T18:00:00+05:30',
                'notes' => 'Internal maintenance trip',
            ]);

            $custodyUse = $useService->transition($context, $initialUse->id, $initialUse->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T08:30:00+05:30',
                'odometer' => '1000.000',
                'reason' => 'Handover for trip',
            ]);

            // Replacement company vehicle
            $replacementVehicle = Vehicle::query()->forceCreate([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'vehicle_number' => 'COMP-03',
                'status' => VehicleStatus::Active,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $replacementVehicle->id,
                'owner_type' => VehicleOwnerType::Company->value,
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => VehicleOwnershipType::CompanyOwned->value,
                'started_at' => '2026-01-01 00:00:00',
            ]);

            $reassignService = app(VehicleDriverReassignmentService::class);
            $reassignment = $reassignService->replaceVehicle($context, $custodyUse->id, [
                'new_vehicle_id' => $replacementVehicle->id,
                'cutover_at' => '2026-09-08T12:00:00+05:30',
                'return_odometer' => '1200.000',
                'new_handover_odometer' => '2000.000',
                'reason' => 'Scheduled maintenance rotation',
            ], $custodyUse->row_version);

            self::assertSame($companyVehicle->id, (int) $reassignment->previous_vehicle_id);
            self::assertSame($replacementVehicle->id, (int) $reassignment->new_vehicle_id);
            self::assertSame(VehicleUseStatus::Returned, $custodyUse->refresh()->status);
        });
    }

    public function test_running_chart_and_use_register_filtering_by_scope(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $agreements = app(AgreementService::class);
            $customer = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $customer = $agreements->change(AgreementKind::Customer, $context, $customer->id, $customer->row_version, AgreementAction::Activate);

            $owner = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $owner = $agreements->change(AgreementKind::Owner, $context, $owner->id, $owner->row_version, AgreementAction::Activate);

            $useService = app(VehicleUseService::class);
            $chartService = app(RunningChartService::class);

            // 1. Scenario 3: Third-party vehicle sub-leased to Customer
            $customerUse = $useService->plan($context, $customer->id, $customer->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $owner->id,
                'starts_at' => '2026-09-07T08:00:00+05:30',
                'ends_at' => '2026-09-07T20:00:00+05:30',
                'notes' => 'Customer rental with owner vehicle',
            ]);
            $custody1 = $useService->transition($context, $customerUse->id, $customerUse->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T08:00:00+05:30',
                'odometer' => '100.000',
                'reason' => 'Handover',
            ]);
            $chart1 = $chartService->create($context, $custody1->id, $custody1->row_version, [
                'reference' => 'RC-CUST-001',
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-07T18:00:00+05:30',
                'start_odometer' => '100.000',
                'end_odometer' => '200.000',
            ]);

            // 2. Scenario 1b: Company-owned vehicle used internally
            $companyVehicle = Vehicle::query()->forceCreate([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'vehicle_number' => 'INT-COMP-01',
                'status' => VehicleStatus::Active,
            ]);
            DB::table('vehicle_ownerships')->insert([
                'tenant_id' => $context->tenantId,
                'vehicle_id' => $companyVehicle->id,
                'owner_type' => VehicleOwnerType::Company->value,
                'owner_key' => 'company',
                'owner_code_snapshot' => 'COMPANY',
                'owner_name_snapshot' => 'Company',
                'ownership_type' => VehicleOwnershipType::CompanyOwned->value,
                'started_at' => '2026-01-01 00:00:00',
            ]);

            $internalUse = $useService->planInternal($context, [
                'vehicle_id' => $companyVehicle->id,
                'starts_at' => '2026-09-07T08:00:00+05:30',
                'ends_at' => '2026-09-07T20:00:00+05:30',
                'notes' => 'Internal maintenance',
            ]);
            $custody2 = $useService->transition($context, $internalUse->id, $internalUse->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-07T08:00:00+05:30',
                'odometer' => '500.000',
                'reason' => 'Handover internal',
            ]);
            $chart2 = $chartService->create($context, $custody2->id, $custody2->row_version, [
                'reference' => 'RC-INT-002',
                'starts_at' => '2026-09-07T09:00:00+05:30',
                'ends_at' => '2026-09-07T18:00:00+05:30',
                'start_odometer' => '500.000',
                'end_odometer' => '600.000',
            ]);

            // Test Running Chart Register filtering
            $chartRegister = app(RunningChartRegisterService::class);

            // A: kind=customer MUST include chart1 (customer rental even though it is owner-supplied!)
            $customerCharts = $chartRegister->list($context, ['kind' => 'customer'], 10);
            self::assertSame(1, $customerCharts->total());
            self::assertSame('RC-CUST-001', $customerCharts->items()[0]->reference);

            // B: kind=owner MUST include chart1 (because it is owner-supplied)
            $ownerCharts = $chartRegister->list($context, ['kind' => 'owner'], 10);
            self::assertSame(1, $ownerCharts->total());
            self::assertSame('RC-CUST-001', $ownerCharts->items()[0]->reference);

            // C: kind=internal MUST include chart2 (internal company vehicle, no customer)
            $internalCharts = $chartRegister->list($context, ['kind' => 'internal'], 10);
            self::assertSame(1, $internalCharts->total());
            self::assertSame('RC-INT-002', $internalCharts->items()[0]->reference);

            // Test Vehicle Use Register filtering
            $useRegister = app(VehicleUseRegisterService::class);

            $customerUses = $useRegister->list($context, ['kind' => 'customer'], 10);
            self::assertSame(1, $customerUses->total());
            self::assertSame($customerUse->id, $customerUses->items()[0]->id);

            $internalUses = $useRegister->list($context, ['kind' => 'internal'], 10);
            self::assertSame(1, $internalUses->total());
            self::assertSame($internalUse->id, $internalUses->items()[0]->id);
        });
    }
}
