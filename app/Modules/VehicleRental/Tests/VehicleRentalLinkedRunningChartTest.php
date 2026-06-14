<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Reporting\Services\ReportCatalog;
use Modules\Reporting\Services\ReportQueryBuilder;
use Modules\VehicleRental\DTOs\RentalAgreementData;
use Modules\VehicleRental\DTOs\RentalAgreementVehicleData;
use Modules\VehicleRental\DTOs\RentalAgreementVehicleLinkData;
use Modules\VehicleRental\DTOs\RentalInspectionData;
use Modules\VehicleRental\DTOs\RentalRateSnapshotData;
use Modules\VehicleRental\DTOs\RentalUsageEventData;
use Modules\VehicleRental\DTOs\RentalUsageLogData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Enums\RentalType;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalAgreementVehicleLink;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalAgreementVehicleLinkService;
use Modules\VehicleRental\Services\RentalAgreementVehicleService;
use Modules\VehicleRental\Services\RentalChargeCalculationService;
use Modules\VehicleRental\Services\RentalChargeService;
use Modules\VehicleRental\Services\RentalInvoiceIntegrationService;
use Modules\VehicleRental\Services\RentalPickupService;
use Modules\VehicleRental\Services\RentalReturnService;
use Modules\VehicleRental\Services\RentalUsageEventService;
use Modules\VehicleRental\Services\RentalUsageLogService;
use Tests\TestCase;

final class VehicleRentalLinkedRunningChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_running_chart_drives_revenue_cost_customer_invoice_supplier_payable_and_margin(): void
    {
        $context = $this->context();
        [$outbound, $outboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-CUSTOMER',
            '100.000000',
            '120.000000',
            '1500.000000',
            '3000.000000',
        );
        [$inbound, $inboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Inbound,
            'AGR-OWNER',
            '50.000000',
            '80.000000',
            '900.000000',
            '2000.000000',
        );

        $link = app(RentalAgreementVehicleLinkService::class)->create(
            $context['tenant_id'],
            $context['organization_unit_id'],
            new RentalAgreementVehicleLinkData(
                inboundAgreementVehicleId: (int) $inboundAllocation->getKey(),
                outboundAgreementVehicleId: (int) $outboundAllocation->getKey(),
                effectiveFrom: '2026-06-01 08:00:00',
                effectiveTo: '2026-06-03 08:00:00',
            ),
        );

        $usage = app(RentalUsageLogService::class)->create($outbound->refresh(), new RentalUsageLogData(
            agreementVehicleId: (int) $outboundAllocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1000.000000',
            endOdometer: '1100.000000',
            startTime: '08:00',
            endTime: '13:00',
            tripFrom: 'Colombo',
            tripTo: 'Galle',
            tripPurpose: 'Customer delivery route',
        ));
        app(RentalUsageEventService::class)->create($usage, new RentalUsageEventData(
            RentalUsageEventType::Overtime,
            '3.000000',
        ));
        app(RentalUsageEventService::class)->create($usage, new RentalUsageEventData(
            RentalUsageEventType::NightOut,
            '1.000000',
        ));
        app(RentalUsageLogService::class)->changeStatus($usage, RentalUsageLogStatus::Submitted);
        $usage = app(RentalUsageLogService::class)->changeStatus(
            $usage->refresh(),
            RentalUsageLogStatus::Approved,
        );

        $outboundCharges = app(RentalChargeCalculationService::class)->calculate($outbound->refresh());
        $inboundCharges = app(RentalChargeCalculationService::class)->calculate($inbound->refresh());
        $math = app(DecimalMath::class);
        $revenue = $math->sum($outboundCharges->pluck('total_amount')->map(fn ($value): string => (string) $value)->all());
        $cost = $math->sum($inboundCharges->pluck('total_amount')->map(fn ($value): string => (string) $value)->all());

        $this->assertDatabaseCount('rental_usage_logs', 1);
        $this->assertDatabaseCount('rental_usage_contexts', 2);
        $this->assertSame(
            ['cost', 'revenue'],
            $usage->contexts()->orderBy('financial_side')->get()
                ->map(fn ($context): string => $context->financial_side->value)
                ->all(),
        );
        $this->assertSame('13700.000000', $revenue);
        $this->assertSame('8800.000000', $cost);
        $this->assertSame('4900.000000', $math->sub($revenue, $cost));
        $this->assertDatabaseHas('rental_charge_calculations', [
            'usage_log_id' => $usage->getKey(),
            'agreement_id' => $outbound->getKey(),
            'financial_side' => 'revenue',
            'calculation_type' => 'overtime',
            'rate' => '1500.000000',
        ]);
        $this->assertDatabaseHas('rental_charge_calculations', [
            'usage_log_id' => $usage->getKey(),
            'agreement_id' => $inbound->getKey(),
            'financial_side' => 'cost',
            'calculation_type' => 'overtime',
            'rate' => '900.000000',
        ]);

        $this->returnAgreement($outbound, $outboundAllocation);
        $this->returnAgreement($inbound, $inboundAllocation);
        app(RentalChargeService::class)->approveAgreementCharges($outbound->refresh());
        app(RentalChargeService::class)->approveAgreementCharges($inbound->refresh());

        $customerInvoice = app(RentalInvoiceIntegrationService::class)->create(
            $outbound->refresh(),
            '2026-06-03',
        );
        $supplierPayable = app(RentalInvoiceIntegrationService::class)->create(
            $inbound->refresh(),
            '2026-06-03',
        );

        $this->assertSame(InvoiceDirection::Outbound, $customerInvoice->direction);
        $this->assertSame('customer', $customerInvoice->party_type);
        $this->assertSame($context['customer_id'], (int) $customerInvoice->party_id);
        $this->assertSame('13700.000000', (string) $customerInvoice->grand_total);
        $this->assertSame(InvoiceDirection::Inbound, $supplierPayable->direction);
        $this->assertSame('supplier', $supplierPayable->party_type);
        $this->assertSame($context['supplier_id'], (int) $supplierPayable->party_id);
        $this->assertSame('8800.000000', (string) $supplierPayable->grand_total);
        $this->assertSame(
            RentalAgreementVehicleLink::class,
            app(ReportCatalog::class)->get('vehicle-rental.profitability')->model,
        );
        $this->assertSame((int) $link->getKey(), (int) $usage->contexts->first()->agreement_vehicle_link_id);
        $definition = app(ReportCatalog::class)->get('vehicle-rental.profitability');
        $reportRows = app(ReportQueryBuilder::class)->rows(
            $definition,
            app(ReportQueryBuilder::class)->query(
                $definition,
                $context['tenant_id'],
                $context['organization_unit_id'],
                [],
            )->get(),
        );
        $this->assertSame('13700.000000', $reportRows[0]['revenue']);
        $this->assertSame('8800.000000', $reportRows[0]['cost']);
        $this->assertSame('4900.000000', $reportRows[0]['profit']);
    }

    public function test_running_chart_can_start_from_inbound_agreement_and_resolve_outbound_context(): void
    {
        $context = $this->context();
        [$outbound, $outboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-CUSTOMER-INBOUND-START',
            '100.000000',
            '120.000000',
            '1500.000000',
            '3000.000000',
        );
        [$inbound, $inboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Inbound,
            'AGR-OWNER-INBOUND-START',
            '50.000000',
            '80.000000',
            '900.000000',
            '2000.000000',
        );
        app(RentalAgreementVehicleLinkService::class)->create(
            $context['tenant_id'],
            $context['organization_unit_id'],
            new RentalAgreementVehicleLinkData(
                inboundAgreementVehicleId: (int) $inboundAllocation->getKey(),
                outboundAgreementVehicleId: (int) $outboundAllocation->getKey(),
                effectiveFrom: '2026-06-01 08:00:00',
                effectiveTo: '2026-06-03 08:00:00',
            ),
        );

        $usage = app(RentalUsageLogService::class)->create($inbound->refresh(), new RentalUsageLogData(
            agreementVehicleId: (int) $inboundAllocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1000.000000',
            endOdometer: '1010.000000',
            startTime: '22:00',
            endTime: '02:00',
        ));

        $this->assertSame((int) $inbound->getKey(), (int) $usage->agreement_id);
        $this->assertSame(240, $usage->working_minutes);
        $this->assertSame(
            [(int) $outbound->getKey(), (int) $inbound->getKey()],
            $usage->contexts()->orderBy('agreement_id')->pluck('agreement_id')->map(
                fn ($id): int => (int) $id,
            )->all(),
        );
    }

    public function test_approved_mileage_chain_is_immutable_and_variance_requires_reason(): void
    {
        $context = $this->context();
        [$agreement, $allocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-MILEAGE',
            '100.000000',
            '120.000000',
            '1500.000000',
            '3000.000000',
        );
        $service = app(RentalUsageLogService::class);
        $first = $service->create($agreement, new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-01',
            startOdometer: '1000.000000',
            endOdometer: '1010.000000',
            startTime: '09:00',
            endTime: '10:00',
        ));
        try {
            app(RentalUsageEventService::class)->create($first, new RentalUsageEventData(
                RentalUsageEventType::Weekend,
                '1.000000',
            ));
            $this->fail('A weekday running chart must not accept weekend classification.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('weekend usage date', $exception->getMessage());
        }
        $service->changeStatus($first, RentalUsageLogStatus::Submitted);
        $service->changeStatus($first->refresh(), RentalUsageLogStatus::Approved);

        $second = $service->create($agreement->refresh(), new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1012.000000',
            endOdometer: '1020.000000',
            startTime: '09:00',
            endTime: '10:00',
        ));
        $service->changeStatus($second, RentalUsageLogStatus::Submitted);

        try {
            $service->changeStatus($second->refresh(), RentalUsageLogStatus::Approved);
            $this->fail('Mileage discontinuity should require a controlled override.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('previous approved finish odometer', $exception->getMessage());
        }
        $second = $service->changeStatus(
            $second->refresh(),
            RentalUsageLogStatus::Approved,
            reason: 'Workshop movement documented outside the running chart.',
            allowMileageVariance: true,
        );

        $this->assertSame('18.000000', (string) $second->cumulative_km);
        $this->assertSame(
            'Workshop movement documented outside the running chart.',
            $second->odometer_variance_reason,
        );
        $this->expectException(InvalidArgumentException::class);
        app(RentalUsageEventService::class)->create($second, new RentalUsageEventData(
            RentalUsageEventType::NightOut,
            '1.000000',
        ));
    }

    /**
     * @return array{0: RentalAgreement, 1: RentalAgreementVehicle}
     */
    private function activeAgreement(
        array $context,
        RentalAgreementDirection $direction,
        string $number,
        string $baseRate,
        string $extraKmRate,
        string $overtimeRate,
        string $nightOutRate,
    ): array {
        $outbound = $direction === RentalAgreementDirection::Outbound;
        $agreement = app(RentalAgreementService::class)->create(new RentalAgreementData(
            tenantId: $context['tenant_id'],
            direction: $direction,
            partyType: $outbound ? RentalPartyType::Customer : RentalPartyType::Supplier,
            partyId: $outbound ? $context['customer_id'] : $context['supplier_id'],
            rentalType: RentalType::Daily,
            billingCycle: RentalBillingCycle::Final,
            agreementDate: '2026-06-01',
            startAt: '2026-06-01 08:00:00',
            expectedEndAt: '2026-06-03 08:00:00',
            rateSnapshot: new RentalRateSnapshotData(
                baseRate: $baseRate,
                rateUnit: RentalRateUnit::Day,
                allowedHours: '8.000000',
                allowedKm: '50.000000',
                extraKmRate: $extraKmRate,
                overtimeRate: $overtimeRate,
                nightOutRate: $nightOutRate,
            ),
            organizationUnitId: $context['organization_unit_id'],
            agreementNumber: $number,
        ));
        $allocation = app(RentalAgreementVehicleService::class)->allocate(
            $agreement,
            new RentalAgreementVehicleData(
                vehicleId: $context['vehicle_id'],
                allocatedFrom: '2026-06-01 08:00:00',
                allocatedTo: '2026-06-03 08:00:00',
                startOdometer: '1000.000000',
            ),
        );
        app(RentalAgreementService::class)->changeStatus($agreement, RentalAgreementStatus::Confirmed);
        app(RentalPickupService::class)->save(
            $agreement->refresh(),
            $allocation->refresh(),
            new RentalInspectionData(
                inspectedAt: '2026-06-01 08:00:00',
                odometer: '1000.000000',
            ),
        );
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Active);

        return [$agreement->refresh(), $allocation->refresh()];
    }

    private function returnAgreement(RentalAgreement $agreement, RentalAgreementVehicle $allocation): void
    {
        app(RentalReturnService::class)->save(
            $agreement->refresh(),
            $allocation->refresh(),
            new RentalInspectionData(
                inspectedAt: '2026-06-03 08:00:00',
                odometer: '1100.000000',
            ),
        );
        app(RentalAgreementService::class)->changeStatus(
            $agreement->refresh(),
            RentalAgreementStatus::Returned,
        );
    }

    /**
     * @return array<string, int>
     */
    private function context(): array
    {
        $suffix = Str::upper(Str::random(6));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-LINK-'.$suffix,
            'name' => 'Linked Rental '.$suffix,
            'slug' => 'linked-rental-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Linked Rental Branch',
            'code' => 'ORG-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => 'CUS-'.$suffix,
            'code' => 'CUS-'.$suffix,
            'name' => 'Customer '.$suffix,
            'display_name' => 'Customer '.$suffix,
            'customer_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $supplierId = (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'supplier_number' => 'SUP-'.$suffix,
            'code' => 'SUP-'.$suffix,
            'name' => 'Supplier '.$suffix,
            'display_name' => 'Supplier '.$suffix,
            'supplier_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_number' => 'VEH-'.$suffix,
            'registration_number' => 'REG-'.$suffix,
            'odometer_reading' => '1000.000000',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $customerId,
            'supplier_id' => $supplierId,
            'vehicle_id' => $vehicleId,
        ];
    }
}
