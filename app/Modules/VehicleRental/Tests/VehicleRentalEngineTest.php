<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\InvoiceSourceLine;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Reporting\Services\ReportCatalog;
use Modules\Reporting\Services\ReportQueryBuilder;
use Modules\VehicleRental\DTOs\RentalAgreementData;
use Modules\VehicleRental\DTOs\RentalAgreementVehicleData;
use Modules\VehicleRental\DTOs\RentalExpenseData;
use Modules\VehicleRental\DTOs\RentalInspectionData;
use Modules\VehicleRental\DTOs\RentalRateSnapshotData;
use Modules\VehicleRental\DTOs\RentalReservationData;
use Modules\VehicleRental\DTOs\RentalUsageEventData;
use Modules\VehicleRental\DTOs\RentalUsageLogData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalExpenseFinancialTreatment;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Enums\RentalType;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalChargeCalculation;
use Modules\VehicleRental\Models\RentalInvoiceLink;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalAgreementVehicleService;
use Modules\VehicleRental\Services\RentalAvailabilityService;
use Modules\VehicleRental\Services\RentalChargeCalculationService;
use Modules\VehicleRental\Services\RentalChargeService;
use Modules\VehicleRental\Services\RentalExpenseService;
use Modules\VehicleRental\Services\RentalInvoiceIntegrationService;
use Modules\VehicleRental\Services\RentalPaymentIntegrationService;
use Modules\VehicleRental\Services\RentalPickupService;
use Modules\VehicleRental\Services\RentalReservationService;
use Modules\VehicleRental\Services\RentalReturnService;
use Modules\VehicleRental\Services\RentalUsageEventService;
use Modules\VehicleRental\Services\RentalUsageLogService;
use Tests\TestCase;

final class VehicleRentalEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_outbound_and_inbound_agreements_with_frozen_rates(): void
    {
        $context = $this->context();
        $outbound = $this->agreement($context, RentalAgreementDirection::Outbound);
        $inbound = $this->agreement($context, RentalAgreementDirection::Inbound);

        $this->assertSame(RentalPartyType::Customer, $outbound->party_type);
        $this->assertSame($context['customer_id'], (int) $outbound->party_id);
        $this->assertSame('100.000000', (string) $outbound->rateSnapshot->base_rate);
        $this->assertSame(RentalPartyType::Supplier, $inbound->party_type);
        $this->assertSame($context['supplier_id'], (int) $inbound->party_id);
        $this->assertSame('50.000000', (string) $inbound->rateSnapshot->allowed_km);
        $this->assertDatabaseCount('rental_status_histories', 2);
    }

    public function test_reservations_and_allocations_prevent_overlaps_but_allow_non_overlapping_future_periods(): void
    {
        $context = $this->context();
        $reservations = app(RentalReservationService::class);
        $reservation = $reservations->create(new RentalReservationData(
            tenantId: $context['tenant_id'],
            direction: RentalAgreementDirection::Outbound,
            partyType: RentalPartyType::Customer,
            partyId: $context['customer_id'],
            rentalType: RentalType::Daily,
            startAt: '2026-06-20 08:00:00',
            expectedEndAt: '2026-06-22 08:00:00',
            organizationUnitId: $context['organization_unit_id'],
            reservationNumber: 'RRES-OVERLAP',
            vehicleId: $context['vehicle_id'],
        ));
        $reservations->changeStatus($reservation, RentalReservationStatus::Confirmed);

        $availability = app(RentalAvailabilityService::class);
        $overlap = $availability->check(
            $context['tenant_id'],
            $context['organization_unit_id'],
            $context['vehicle_id'],
            '2026-06-21 08:00:00',
            '2026-06-23 08:00:00',
        );
        $future = $availability->check(
            $context['tenant_id'],
            $context['organization_unit_id'],
            $context['vehicle_id'],
            '2026-06-23 08:00:00',
            '2026-06-24 08:00:00',
        );

        $this->assertFalse($overlap['available']);
        $this->assertStringContainsString('reserved', implode(' ', $overlap['reasons']));
        $this->assertTrue($future['available']);

        $agreement = $this->agreement($context, RentalAgreementDirection::Outbound, 'AGR-ALLOC');
        $allocation = app(RentalAgreementVehicleService::class)->allocate($agreement, new RentalAgreementVehicleData(
            vehicleId: $context['vehicle_id'],
            allocatedFrom: '2026-06-01 08:00:00',
            allocatedTo: '2026-06-03 08:00:00',
            startOdometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement, RentalAgreementStatus::Confirmed);

        $this->assertSame($context['vehicle_id'], (int) $allocation->vehicle_id);
        $this->assertFalse($availability->check(
            $context['tenant_id'],
            $context['organization_unit_id'],
            $context['vehicle_id'],
            '2026-06-02 08:00:00',
            '2026-06-02 18:00:00',
        )['available']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('overlapping rental agreement');
        app(RentalAgreementVehicleService::class)->allocate(
            $this->agreement($context, RentalAgreementDirection::Outbound, 'AGR-DOUBLE'),
            new RentalAgreementVehicleData(
                vehicleId: $context['vehicle_id'],
                allocatedFrom: '2026-06-02 08:00:00',
                allocatedTo: '2026-06-04 08:00:00',
                startOdometer: '1000.000000',
            ),
        );
    }

    public function test_pickup_return_usage_events_expenses_and_charge_calculation_are_auditable(): void
    {
        $context = $this->context();
        [$agreement, $allocation] = $this->activeAgreement($context);

        $usage = app(RentalUsageLogService::class)->create($agreement->refresh(), new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1000.000000',
            endOdometer: '1100.000000',
            driverId: $context['employee_id'],
            startTime: '08:00',
            endTime: '18:00',
            tripFrom: 'Colombo',
            tripTo: 'Galle',
        ));
        $event = app(RentalUsageEventService::class)->create($usage, new RentalUsageEventData(
            RentalUsageEventType::Overtime,
            '2.000000',
        ));
        app(RentalUsageLogService::class)->changeStatus($usage, RentalUsageLogStatus::Submitted);
        $usage = app(RentalUsageLogService::class)->changeStatus(
            $usage->refresh(),
            RentalUsageLogStatus::Approved,
        );
        $expense = app(RentalExpenseService::class)->create($agreement, new RentalExpenseData(
            RentalExpenseType::Fuel,
            '2026-06-02',
            '30.000000',
            RentalExpenseFinancialTreatment::CustomerBillable,
            (int) $usage->getKey(),
        ));
        app(RentalExpenseService::class)->changeStatus($expense, RentalExpenseStatus::Submitted);
        app(RentalExpenseService::class)->changeStatus($expense, RentalExpenseStatus::Approved);
        $return = app(RentalReturnService::class)->save($agreement, $allocation->refresh(), new RentalInspectionData(
            inspectedAt: '2026-06-03 08:00:00',
            odometer: '1100.000000',
            fuelLevel: '45.000000',
            damageNotes: 'Minor bumper scratch',
            damageAmount: '40.000000',
            isDamageBillable: true,
        ));
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Returned);
        $calculator = app(RentalChargeCalculationService::class);
        $preview = $calculator->preview($agreement->refresh());

        $this->assertSame('390.000000', app(DecimalMath::class)->sum(
            $preview->pluck('total_amount')->map(fn ($value): string => (string) $value)->all(),
        ));
        $this->assertDatabaseCount('rental_charge_calculations', 0);
        $this->assertDatabaseCount('rental_charges', 0);
        $this->assertSame(RentalExpenseStatus::Approved, $expense->refresh()->status);

        $charges = $calculator->calculate($agreement->refresh());

        $this->assertSame('100.000000', (string) $usage->distance_km);
        $this->assertSame('100.000000', (string) $usage->cumulative_km);
        $this->assertSame('2.000000', (string) $event->quantity);
        $this->assertSame('1100.000000', (string) $return->odometer);
        $this->assertSame(RentalExpenseStatus::Charged, $expense->refresh()->status);
        $this->assertSame(
            ['base_rental', 'damage', 'extra_km', 'fuel', 'overtime'],
            $charges->pluck('charge_type')->sort()->values()->all(),
        );
        $this->assertSame('390.000000', app(DecimalMath::class)->sum(
            $charges->pluck('total_amount')->map(fn ($value): string => (string) $value)->all(),
        ));
        $this->assertSame($charges->count(), $agreement->chargeCalculations()->count());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already been generated');
        app(RentalChargeCalculationService::class)->calculate($agreement->refresh());
    }

    public function test_partial_invoicing_prevents_duplicates_and_payment_links_settle_existing_invoice(): void
    {
        $context = $this->context();
        [$agreement, $allocation] = $this->activeAgreement($context, 'AGR-FIN');
        app(RentalReturnService::class)->save($agreement, $allocation->refresh(), new RentalInspectionData(
            inspectedAt: '2026-06-03 08:00:00',
            odometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Returned);
        $charges = app(RentalChargeCalculationService::class)->calculate($agreement->refresh());
        $base = $charges->firstWhere('charge_type', 'base_rental');
        $base->forceFill([
            'withholding_amount' => '20.000000',
            'total_amount' => '180.000000',
        ])->save();
        $base->calculation->forceFill([
            'withholding_amount' => '20.000000',
            'total_amount' => '180.000000',
        ])->save();
        app(RentalChargeService::class)->approveAgreementCharges($agreement->refresh());
        $invoices = app(RentalInvoiceIntegrationService::class);

        $preview = $invoices->preview($agreement->refresh(), '2026-06-03', [
            (int) $base->getKey() => '1.000000',
        ]);
        $first = $invoices->create($agreement->refresh(), '2026-06-03', [
            (int) $base->getKey() => '1.000000',
        ]);
        $remaining = $invoices->billableCharges($agreement->refresh())->firstWhere('id', $base->getKey());
        $second = $invoices->create($agreement->refresh(), '2026-06-03', [
            (int) $base->getKey() => '1.000000',
        ]);

        $statuses = app(InvoiceStatusService::class);
        $first = $statuses->transition($first, InvoiceStatus::Approved);
        $first = $statuses->transition($first, InvoiceStatus::Posted);

        $this->assertSame('90.000000', $preview->grandTotal);
        $this->assertSame('90.000000', (string) $first->grand_total);
        $this->assertSame('1.000000', (string) $remaining->invoiced_quantity);
        $this->assertSame('1.000000', (string) $remaining->remaining_invoice_quantity);
        $this->assertSame('90.000000', (string) $second->grand_total);
        $this->assertSame(2, RentalInvoiceLink::query()->where('charge_id', $base->getKey())->count());
        $this->assertSame(2, InvoiceSourceLine::query()->where('source_line_id', $base->getKey())->count());
        $this->assertDatabaseHas('invoice_adjustments', [
            'invoice_id' => $first->getKey(),
            'adjustment_type' => 'withholding',
            'effect' => 'decrease',
            'amount' => '10.000000',
            'is_system_generated' => true,
        ]);

        $payment = app(RentalPaymentIntegrationService::class)->create(
            $agreement->refresh(),
            'settlement',
            '2026-06-03',
            '40.000000',
            (int) $first->getKey(),
            $context['payment_method_id'],
        );
        $this->assertSame('40.000000', (string) $payment->total_amount);
        $this->assertSame('50.000000', (string) $first->refresh()->balance_due);
        $this->assertDatabaseHas('rental_payment_links', [
            'agreement_id' => $agreement->getKey(),
            'payment_id' => $payment->getKey(),
            'invoice_id' => $first->getKey(),
            'link_type' => 'settlement',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No approved rental charges remain to invoice.');
        $invoices->create($agreement->refresh(), '2026-06-03', [
            (int) $base->getKey() => '1.000000',
        ]);
    }

    public function test_regeneration_supersedes_draft_calculations_without_deleting_history(): void
    {
        $context = $this->context();
        [$agreement, $allocation] = $this->activeAgreement($context, 'AGR-REGENERATE');
        app(RentalReturnService::class)->save($agreement, $allocation->refresh(), new RentalInspectionData(
            inspectedAt: '2026-06-03 08:00:00',
            odometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Returned);
        $service = app(RentalChargeCalculationService::class);
        $firstCharges = $service->calculate($agreement->refresh());
        $firstCalculation = RentalChargeCalculation::query()->firstOrFail();

        $secondCharges = $service->calculate($agreement->refresh(), true);
        $secondCalculation = RentalChargeCalculation::query()
            ->where('calculation_version', 2)
            ->firstOrFail();

        $this->assertSame('superseded', $firstCalculation->refresh()->status);
        $this->assertSame('cancelled', $firstCharges->sole()->refresh()->status->value);
        $this->assertSame((int) $firstCalculation->getKey(), (int) $secondCalculation->supersedes_calculation_id);
        $this->assertSame('draft', $secondCalculation->status);
        $this->assertSame('draft', $secondCharges->sole()->status->value);
        $this->assertDatabaseCount('rental_charge_calculations', 2);
        $this->assertDatabaseCount('rental_charges', 2);
    }

    public function test_daily_billing_periods_reset_allowances_and_active_agreements_can_invoice(): void
    {
        $context = $this->context();
        $agreement = app(RentalAgreementService::class)->create(new RentalAgreementData(
            tenantId: $context['tenant_id'],
            direction: RentalAgreementDirection::Outbound,
            partyType: RentalPartyType::Customer,
            partyId: $context['customer_id'],
            rentalType: RentalType::Daily,
            billingCycle: RentalBillingCycle::Daily,
            agreementDate: '2026-06-01',
            startAt: '2026-06-01 08:00:00',
            expectedEndAt: '2026-06-04 08:00:00',
            rateSnapshot: new RentalRateSnapshotData(
                baseRate: '100.000000',
                rateUnit: RentalRateUnit::Day,
                allowedKm: '50.000000',
                extraKmRate: '2.000000',
            ),
            organizationUnitId: $context['organization_unit_id'],
            agreementNumber: 'AGR-DAILY-PERIODS',
        ));
        $allocation = app(RentalAgreementVehicleService::class)->allocate($agreement, new RentalAgreementVehicleData(
            vehicleId: $context['vehicle_id'],
            allocatedFrom: '2026-06-01 08:00:00',
            allocatedTo: '2026-06-04 08:00:00',
            startOdometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement, RentalAgreementStatus::Confirmed);
        app(RentalPickupService::class)->save($agreement->refresh(), $allocation->refresh(), new RentalInspectionData(
            inspectedAt: '2026-06-01 08:00:00',
            odometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Active);

        $first = $this->approveUsage(app(RentalUsageLogService::class), $agreement->refresh(), $allocation->refresh(), '09:00', '1000.000000', '1100.000000', '2026-06-01');
        $second = $this->approveUsage(app(RentalUsageLogService::class), $agreement->refresh(), $allocation->refresh(), '09:00', '1100.000000', '1200.000000', '2026-06-02');

        $charges = app(RentalChargeCalculationService::class)->calculate($agreement->refresh());
        app(RentalChargeService::class)->approveAgreementCharges($agreement->refresh());
        $invoice = app(RentalInvoiceIntegrationService::class)->create($agreement->refresh(), '2026-06-04');

        $this->assertSame('100.000000', (string) $first->distance_km);
        $this->assertSame('100.000000', (string) $second->distance_km);
        $this->assertSame(3, DB::table('rental_billing_periods')->where('agreement_id', $agreement->getKey())->count());
        $this->assertSame(3, DB::table('rental_charge_runs')->where('agreement_id', $agreement->getKey())->count());
        $this->assertSame('500.000000', app(DecimalMath::class)->sum(
            $charges->pluck('total_amount')->map(fn ($value): string => (string) $value)->all(),
        ));
        $this->assertSame('500.000000', (string) $invoice->grand_total);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already been generated');
        app(RentalChargeCalculationService::class)->calculate($agreement->refresh());
    }

    public function test_anniversary_month_billing_handles_leap_year_end_of_month(): void
    {
        $context = $this->context();
        $agreement = app(RentalAgreementService::class)->create(new RentalAgreementData(
            tenantId: $context['tenant_id'],
            direction: RentalAgreementDirection::Outbound,
            partyType: RentalPartyType::Customer,
            partyId: $context['customer_id'],
            rentalType: RentalType::Monthly,
            billingCycle: RentalBillingCycle::Monthly,
            agreementDate: '2028-01-31',
            startAt: '2028-01-31 08:00:00',
            expectedEndAt: '2028-03-31 08:00:00',
            rateSnapshot: new RentalRateSnapshotData(
                baseRate: '100.000000',
                rateUnit: RentalRateUnit::Month,
            ),
            organizationUnitId: $context['organization_unit_id'],
            agreementNumber: 'AGR-LEAP-MONTH',
            billingBasis: RentalBillingBasis::AnniversaryMonth,
        ));
        $allocation = app(RentalAgreementVehicleService::class)->allocate($agreement, new RentalAgreementVehicleData(
            vehicleId: $context['vehicle_id'],
            allocatedFrom: '2028-01-31 08:00:00',
            allocatedTo: '2028-03-31 08:00:00',
            startOdometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement, RentalAgreementStatus::Confirmed);
        app(RentalPickupService::class)->save($agreement->refresh(), $allocation->refresh(), new RentalInspectionData(
            inspectedAt: '2028-01-31 08:00:00',
            odometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Active);
        app(RentalReturnService::class)->save($agreement->refresh(), $allocation->refresh(), new RentalInspectionData(
            inspectedAt: '2028-03-31 08:00:00',
            odometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Returned);

        $charges = app(RentalChargeCalculationService::class)->calculate($agreement->refresh());

        $this->assertSame(['2028-02-29 08:00:00', '2028-03-31 08:00:00'], DB::table('rental_billing_periods')
            ->where('agreement_id', $agreement->getKey())
            ->orderBy('period_sequence')
            ->pluck('period_end')
            ->all());
        $this->assertSame('200.000000', app(DecimalMath::class)->sum(
            $charges->pluck('total_amount')->map(fn ($value): string => (string) $value)->all(),
        ));
    }

    public function test_calendar_month_billing_prorates_partial_first_and_final_periods(): void
    {
        $context = $this->context();
        $agreement = app(RentalAgreementService::class)->create(new RentalAgreementData(
            tenantId: $context['tenant_id'],
            direction: RentalAgreementDirection::Outbound,
            partyType: RentalPartyType::Customer,
            partyId: $context['customer_id'],
            rentalType: RentalType::Monthly,
            billingCycle: RentalBillingCycle::Monthly,
            agreementDate: '2028-01-16',
            startAt: '2028-01-16 00:00:00',
            expectedEndAt: '2028-03-10 00:00:00',
            rateSnapshot: new RentalRateSnapshotData(
                baseRate: '100.000000',
                rateUnit: RentalRateUnit::Month,
            ),
            organizationUnitId: $context['organization_unit_id'],
            agreementNumber: 'AGR-CALENDAR-PRORATE',
            billingBasis: RentalBillingBasis::CalendarMonth,
        ));
        $allocation = app(RentalAgreementVehicleService::class)->allocate($agreement, new RentalAgreementVehicleData(
            vehicleId: $context['vehicle_id'],
            allocatedFrom: '2028-01-16 00:00:00',
            allocatedTo: '2028-03-10 00:00:00',
            startOdometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement, RentalAgreementStatus::Confirmed);
        app(RentalPickupService::class)->save($agreement->refresh(), $allocation->refresh(), new RentalInspectionData(
            inspectedAt: '2028-01-16 00:00:00',
            odometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Active);
        app(RentalReturnService::class)->save($agreement->refresh(), $allocation->refresh(), new RentalInspectionData(
            inspectedAt: '2028-03-10 00:00:00',
            odometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Returned);

        $charges = app(RentalChargeCalculationService::class)->calculate($agreement->refresh());

        $this->assertSame(['2028-02-01 00:00:00', '2028-03-01 00:00:00', '2028-03-10 00:00:00'], DB::table('rental_billing_periods')
            ->where('agreement_id', $agreement->getKey())
            ->orderBy('period_sequence')
            ->pluck('period_end')
            ->all());
        $this->assertSame(['0.516129', '1.000000', '0.290322'], $agreement->chargeCalculations()
            ->where('calculation_type', 'base_rental')
            ->orderBy('period_sequence')
            ->pluck('chargeable_quantity')
            ->map(fn ($value): string => (string) $value)
            ->all());
        $this->assertSame('180.645100', app(DecimalMath::class)->sum(
            $charges->pluck('total_amount')->map(fn ($value): string => (string) $value)->all(),
        ));
    }

    public function test_draft_usage_events_and_expenses_can_be_corrected_and_deleted_only_before_approval(): void
    {
        $context = $this->context();
        [$agreement, $allocation] = $this->activeAgreement($context, 'AGR-DRAFT-CORRECTION');
        $usageService = app(RentalUsageLogService::class);
        $eventService = app(RentalUsageEventService::class);
        $expenseService = app(RentalExpenseService::class);

        $usage = $usageService->create($agreement, new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1000.000000',
            endOdometer: '1010.000000',
            startTime: '09:00',
            endTime: '10:00',
        ));
        $event = $eventService->create($usage, new RentalUsageEventData(RentalUsageEventType::Overtime, '1.000000'));
        $event = $eventService->update($event, new RentalUsageEventData(RentalUsageEventType::Waiting, '2.000000'));
        $usage = $usageService->update($usage, new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1000.000000',
            endOdometer: '1020.000000',
            startTime: '09:00',
            endTime: '11:00',
        ));
        $eventService->delete($event);
        $usageService->delete($usage);

        $expense = $expenseService->create($agreement, new RentalExpenseData(
            RentalExpenseType::Fuel,
            '2026-06-02',
            '30.000000',
            RentalExpenseFinancialTreatment::CustomerBillable,
            originalTaxAmount: '3.000000',
            recoveryBaseAmount: '35.000000',
            recoveryTaxAmount: '4.000000',
        ));
        $expense = $expenseService->update($expense, new RentalExpenseData(
            RentalExpenseType::Fuel,
            '2026-06-02',
            '40.000000',
            RentalExpenseFinancialTreatment::CustomerBillable,
            originalTaxAmount: '5.000000',
            recoveryBaseAmount: '45.000000',
            recoveryTaxAmount: '6.000000',
        ));
        $expenseService->delete($expense);

        $approved = $this->approveUsage($usageService, $agreement->refresh(), $allocation->refresh(), '09:00', '1000.000000', '1010.000000');
        $this->expectException(InvalidArgumentException::class);
        $usageService->update($approved, new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1000.000000',
            endOdometer: '1020.000000',
            startTime: '09:00',
            endTime: '11:00',
        ));
    }

    public function test_expense_treatments_are_scoped_idempotent_and_require_submission_before_approval(): void
    {
        $context = $this->context();
        [$agreement] = $this->activeAgreement($context, 'AGR-EXPENSE-TREATMENTS');
        $service = app(RentalExpenseService::class);
        $customer = $service->create($agreement, new RentalExpenseData(
            RentalExpenseType::Fuel,
            '2026-06-02',
            '30.000000',
            RentalExpenseFinancialTreatment::CustomerBillable,
            receiptNo: 'REC-CUSTOMER',
        ));
        $duplicate = $service->create($agreement, new RentalExpenseData(
            RentalExpenseType::Fuel,
            '2026-06-02',
            '30.000000',
            RentalExpenseFinancialTreatment::CustomerBillable,
            receiptNo: 'REC-CUSTOMER',
        ));
        $supplier = $service->create($agreement, new RentalExpenseData(
            RentalExpenseType::Toll,
            '2026-06-02',
            '15.000000',
            RentalExpenseFinancialTreatment::SupplierRecoverable,
            responsiblePartyId: $context['supplier_id'],
            receiptNo: 'REC-SUPPLIER',
        ));
        $employee = $service->create($agreement, new RentalExpenseData(
            RentalExpenseType::Parking,
            '2026-06-02',
            '10.000000',
            RentalExpenseFinancialTreatment::EmployeeReimbursable,
            responsiblePartyId: $context['employee_id'],
            receiptNo: 'REC-EMPLOYEE',
        ));

        $this->assertSame((int) $customer->getKey(), (int) $duplicate->getKey());
        $this->assertSame('customer', $customer->responsible_party_type);
        $this->assertSame($context['customer_id'], (int) $customer->responsible_party_id);
        $this->assertSame('supplier', $supplier->responsible_party_type);
        $this->assertSame($context['supplier_id'], (int) $supplier->responsible_party_id);
        $this->assertSame('employee', $employee->responsible_party_type);
        $this->assertSame($context['employee_id'], (int) $employee->responsible_party_id);
        $this->assertDatabaseCount('rental_expenses', 3);

        try {
            $service->changeStatus($customer, RentalExpenseStatus::Approved);
            $this->fail('Draft expenses must not be approved directly.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('draft to approved', $exception->getMessage());
        }
        $submitted = $service->changeStatus($customer->refresh(), RentalExpenseStatus::Submitted, 10);
        $approved = $service->changeStatus($submitted->refresh(), RentalExpenseStatus::Approved, 11);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertNotNull($approved->approved_at);
        $this->assertSame(3, $approved->statusHistories()->count());
    }

    public function test_active_agreement_vehicle_replacement_requires_a_new_pickup_before_usage(): void
    {
        $context = $this->context();
        [$agreement, $current] = $this->activeAgreement($context, 'AGR-REPLACE');
        $replacement = app(RentalAgreementVehicleService::class)->replace(
            $agreement,
            $current,
            new RentalAgreementVehicleData(
                vehicleId: $context['replacement_vehicle_id'],
                allocatedFrom: '2026-06-02 08:00:00',
                allocatedTo: '2026-06-03 08:00:00',
                startOdometer: '2000.000000',
            ),
        );

        $this->assertSame('replaced', $current->refresh()->status->value);
        $this->assertSame('active', $replacement->status->value);
        $this->assertSame('rented', $replacement->vehicle->status->value);

        try {
            app(RentalUsageLogService::class)->create($agreement->refresh(), new RentalUsageLogData(
                agreementVehicleId: (int) $replacement->getKey(),
                usageDate: '2026-06-02',
                startOdometer: '2000.000000',
                endOdometer: '2010.000000',
            ));
            $this->fail('Expected replacement usage without pickup inspection to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Pickup inspection is required before recording vehicle usage.', $exception->getMessage());
        }

        $pickup = app(RentalPickupService::class)->save($agreement->refresh(), $replacement, new RentalInspectionData(
            inspectedAt: '2026-06-02 08:00:00',
            odometer: '2000.000000',
        ));
        $this->assertSame('2000.000000', (string) $pickup->odometer);
    }

    public function test_tenant_and_organization_isolation_reject_cross_scope_parties_vehicles_and_links(): void
    {
        $context = $this->context('PRIMARY');
        $otherTenant = $this->context('OTHER');
        $otherOrganizationId = $this->organization($context['tenant_id'], 'ORG-OTHER');
        $otherOrgCustomer = $this->customer($context['tenant_id'], 'CUS-ORG-OTHER', $otherOrganizationId);

        try {
            $this->agreement($context, RentalAgreementDirection::Outbound, 'AGR-CROSS-TENANT', $otherTenant['customer_id']);
            $this->fail('Expected tenant-isolated customer lookup to fail.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }

        try {
            $this->agreement($context, RentalAgreementDirection::Outbound, 'AGR-CROSS-ORG', $otherOrgCustomer);
            $this->fail('Expected organization-isolated customer lookup to fail.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }

        $agreement = $this->agreement($context, RentalAgreementDirection::Outbound, 'AGR-SCOPE');
        $this->expectException(ModelNotFoundException::class);
        app(RentalAgreementVehicleService::class)->allocate($agreement, new RentalAgreementVehicleData(
            vehicleId: $otherTenant['vehicle_id'],
            allocatedFrom: '2026-06-01 08:00:00',
            allocatedTo: '2026-06-03 08:00:00',
            startOdometer: '1000.000000',
        ));
    }

    public function test_reporting_catalog_contains_all_vehicle_rental_reports(): void
    {
        $catalog = app(ReportCatalog::class);
        $keys = collect($catalog->index())
            ->where('group', 'Vehicle Rental')
            ->pluck('key')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([
            'vehicle-rental.active-rentals',
            'vehicle-rental.agreement-register',
            'vehicle-rental.allocation-history',
            'vehicle-rental.charges',
            'vehicle-rental.customer-ageing',
            'vehicle-rental.customer-outstanding',
            'vehicle-rental.day-night-out-summary',
            'vehicle-rental.deposit-liability',
            'vehicle-rental.deposit-refund',
            'vehicle-rental.document-expiry',
            'vehicle-rental.expense-recovery',
            'vehicle-rental.fleet-availability',
            'vehicle-rental.inbound-cost',
            'vehicle-rental.mileage-summary',
            'vehicle-rental.overdue-rentals',
            'vehicle-rental.overtime-summary',
            'vehicle-rental.owner-payable',
            'vehicle-rental.owner-payable-ageing',
            'vehicle-rental.partially-invoiced',
            'vehicle-rental.payment-allocation',
            'vehicle-rental.profitability',
            'vehicle-rental.revenue',
            'vehicle-rental.revenue-licence-expiry',
            'vehicle-rental.running-chart',
            'vehicle-rental.running-chart-customer',
            'vehicle-rental.running-chart-monthly',
            'vehicle-rental.running-chart-owner',
            'vehicle-rental.tax-withholding-traceability',
            'vehicle-rental.uninvoiced-revenue',
            'vehicle-rental.unprocessed-payable-cost',
            'vehicle-rental.usage-summary',
            'vehicle-rental.vehicle-utilization',
        ], $keys);

        $context = $this->context();
        $queries = app(ReportQueryBuilder::class);
        foreach ($keys as $key) {
            $rows = $queries->query(
                $catalog->get($key),
                $context['tenant_id'],
                $context['organization_unit_id'],
                [],
            )->limit(1)->get();
            $this->assertLessThanOrEqual(1, $rows->count(), $key);
        }
    }

    private function approveUsage(
        RentalUsageLogService $service,
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
        string $startTime,
        string $startOdometer,
        string $endOdometer,
        string $usageDate = '2026-06-02',
    ): RentalUsageLog {
        $usage = $service->create($agreement->refresh(), new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: $usageDate,
            startOdometer: $startOdometer,
            endOdometer: $endOdometer,
            startTime: $startTime,
            endTime: CarbonImmutable::parse($usageDate.' '.$startTime)->addHour()->format('H:i'),
        ));
        $service->changeStatus($usage, RentalUsageLogStatus::Submitted);

        return $service->changeStatus($usage->refresh(), RentalUsageLogStatus::Approved);
    }

    /** @return array{0: RentalAgreement, 1: RentalAgreementVehicle} */
    private function activeAgreement(array $context, string $number = 'AGR-LIFECYCLE'): array
    {
        $agreement = $this->agreement($context, RentalAgreementDirection::Outbound, $number);
        $allocation = app(RentalAgreementVehicleService::class)->allocate($agreement, new RentalAgreementVehicleData(
            vehicleId: $context['vehicle_id'],
            allocatedFrom: '2026-06-01 08:00:00',
            allocatedTo: '2026-06-03 08:00:00',
            startOdometer: '1000.000000',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement, RentalAgreementStatus::Confirmed);
        app(RentalPickupService::class)->save($agreement->refresh(), $allocation->refresh(), new RentalInspectionData(
            inspectedAt: '2026-06-01 08:00:00',
            odometer: '1000.000000',
            fuelLevel: '75.000000',
            conditionNotes: 'Good condition',
        ));
        app(RentalAgreementService::class)->changeStatus($agreement->refresh(), RentalAgreementStatus::Active);

        return [$agreement->refresh(), $allocation->refresh()];
    }

    private function agreement(
        array $context,
        RentalAgreementDirection $direction,
        ?string $number = null,
        ?int $partyId = null,
    ): RentalAgreement {
        $outbound = $direction === RentalAgreementDirection::Outbound;

        return app(RentalAgreementService::class)->create(new RentalAgreementData(
            tenantId: $context['tenant_id'],
            direction: $direction,
            partyType: $outbound ? RentalPartyType::Customer : RentalPartyType::Supplier,
            partyId: $partyId ?? ($outbound ? $context['customer_id'] : $context['supplier_id']),
            rentalType: RentalType::Daily,
            billingCycle: RentalBillingCycle::Final,
            agreementDate: '2026-06-01',
            startAt: '2026-06-01 08:00:00',
            expectedEndAt: '2026-06-03 08:00:00',
            rateSnapshot: new RentalRateSnapshotData(
                baseRate: '100.000000',
                rateUnit: RentalRateUnit::Day,
                allowedHours: '8.000000',
                allowedKm: '50.000000',
                extraHourRate: '5.000000',
                extraKmRate: '2.000000',
                overtimeRate: '10.000000',
                doubleOvertimeRate: '20.000000',
                nightShiftRate: '25.000000',
                weekendRate: '30.000000',
                holidayRate: '35.000000',
                driverRate: '40.000000',
                outstationRate: '45.000000',
                dayOutRate: '50.000000',
                nightOutRate: '55.000000',
                fuelRate: '1.000000',
                waitingHourRate: '6.000000',
            ),
            organizationUnitId: $context['organization_unit_id'],
            agreementNumber: $number ?? 'AGR-'.Str::upper(Str::random(6)),
        ));
    }

    /** @return array<string, int> */
    private function context(string $suffix = ''): array
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(5));
        $tenantId = $this->tenant($suffix);
        $organizationUnitId = $this->organization($tenantId, 'ORG-'.$suffix);

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_id' => $this->customer($tenantId, 'CUS-'.$suffix, $organizationUnitId),
            'supplier_id' => $this->supplier($tenantId, 'SUP-'.$suffix, $organizationUnitId),
            'vehicle_id' => $this->vehicle($tenantId, 'VEH-'.$suffix, $organizationUnitId),
            'replacement_vehicle_id' => $this->vehicle($tenantId, 'VEH-REPL-'.$suffix, $organizationUnitId, '2000.000000'),
            'employee_id' => $this->employee($tenantId, 'EMP-'.$suffix, $organizationUnitId),
            'payment_method_id' => $this->paymentMethod($tenantId, $organizationUnitId, $suffix),
        ];
    }

    private function paymentMethod(int $tenantId, int $organizationUnitId, string $suffix): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'scope_key' => 'org:'.$tenantId.':'.$organizationUnitId,
            'code' => 'CASH-'.$suffix,
            'name' => 'Cash',
            'method_type' => 'cash',
            'direction_allowed' => 'both',
            'requires_reference' => false,
            'requires_bank_account' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function tenant(string $suffix): int
    {
        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-VR-'.$suffix,
            'name' => 'Vehicle Rental '.$suffix,
            'slug' => 'vehicle-rental-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function organization(int $tenantId, string $code): int
    {
        return (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $code,
            'code' => $code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function customer(int $tenantId, string $code, int $organizationUnitId): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer_number' => $code,
            'code' => $code,
            'name' => 'Customer '.$code,
            'display_name' => 'Customer '.$code,
            'customer_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function supplier(int $tenantId, string $code, int $organizationUnitId): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'supplier_number' => $code,
            'code' => $code,
            'name' => 'Supplier '.$code,
            'display_name' => 'Supplier '.$code,
            'supplier_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function vehicle(
        int $tenantId,
        string $code,
        int $organizationUnitId,
        string $odometer = '1000.000000',
    ): int {
        return (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'vehicle_number' => $code,
            'registration_number' => 'REG-'.$code,
            'odometer_reading' => $odometer,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function employee(int $tenantId, string $code, int $organizationUnitId): int
    {
        return (int) DB::table('hr_employees')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'employee_number' => $code,
            'code' => $code,
            'first_name' => 'Driver',
            'display_name' => 'Driver '.$code,
            'status' => 'active',
            'availability_status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
