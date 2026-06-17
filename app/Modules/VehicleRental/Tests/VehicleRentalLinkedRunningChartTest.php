<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Reporting\Services\ReportCatalog;
use Modules\Reporting\Services\ReportQueryBuilder;
use Modules\User\Models\UserModel;
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
use Modules\VehicleRental\Models\RentalChargeCalculation;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalAgreementVehicleLinkService;
use Modules\VehicleRental\Services\RentalAgreementVehicleService;
use Modules\VehicleRental\Services\RentalChargeCalculationService;
use Modules\VehicleRental\Services\RentalChargeService;
use Modules\VehicleRental\Services\RentalInvoiceIntegrationService;
use Modules\VehicleRental\Services\RentalPaymentIntegrationService;
use Modules\VehicleRental\Services\RentalPickupService;
use Modules\VehicleRental\Services\RentalReturnService;
use Modules\VehicleRental\Services\RentalUsageContextService;
use Modules\VehicleRental\Services\RentalUsageEventService;
use Modules\VehicleRental\Services\RentalUsageLogService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;
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
            false,
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
        app(RentalAgreementVehicleLinkService::class)->submit($link, null);
        $link = app(RentalAgreementVehicleLinkService::class)->approve($link->refresh(), null);
        app(RentalAgreementService::class)->changeStatus(
            $inbound->refresh(),
            RentalAgreementStatus::Active,
        );
        $inbound = $inbound->refresh();

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
        $supplierPayment = app(RentalPaymentIntegrationService::class)->create(
            $inbound->refresh(),
            'settlement',
            '2026-06-03',
            '3000.000000',
            (int) $supplierPayable->getKey(),
        );
        $this->assertSame('3000.000000', (string) $supplierPayment->total_amount);
        $this->assertSame('5800.000000', (string) $supplierPayable->refresh()->balance_due);
        $this->assertDatabaseHas('rental_payment_links', [
            'agreement_id' => $inbound->getKey(),
            'payment_id' => $supplierPayment->getKey(),
            'invoice_id' => $supplierPayable->getKey(),
            'link_type' => 'settlement',
            'amount' => '3000.000000',
        ]);
        $this->assertSame(
            RentalChargeCalculation::class,
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
        $this->assertSame('13700.000000', $math->sum(array_column($reportRows, 'revenue')));
        $this->assertSame('8800.000000', $math->sum(array_column($reportRows, 'cost')));
        $this->assertSame('4900.000000', $math->sum(array_column($reportRows, 'profit')));
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
            false,
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
        app(RentalAgreementVehicleLinkService::class)->submit($link, null);
        app(RentalAgreementVehicleLinkService::class)->approve($link->refresh(), null);
        app(RentalAgreementService::class)->changeStatus(
            $inbound->refresh(),
            RentalAgreementStatus::Active,
        );
        $inbound = $inbound->refresh();

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

    public function test_standalone_outbound_and_inbound_usage_each_create_one_context(): void
    {
        foreach ([
            [RentalAgreementDirection::Outbound, 'revenue'],
            [RentalAgreementDirection::Inbound, 'cost'],
        ] as [$direction, $financialSide]) {
            $context = $this->context();
            [$agreement, $allocation] = $this->activeAgreement(
                $context,
                $direction,
                'AGR-STANDALONE-'.strtoupper($direction->value),
                '100.000000',
                '10.000000',
                '20.000000',
                '30.000000',
            );

            $usage = app(RentalUsageLogService::class)->create($agreement, new RentalUsageLogData(
                agreementVehicleId: (int) $allocation->getKey(),
                usageDate: '2026-06-02',
                startOdometer: '1000.000000',
                endOdometer: '1010.000000',
                startTime: '09:00',
                endTime: '10:00',
            ));

            $this->assertCount(1, $usage->contexts);
            $this->assertSame($financialSide, $usage->contexts->sole()->financial_side->value);
            $this->assertSame((int) $agreement->getKey(), (int) $usage->contexts->sole()->agreement_id);
        }
    }

    public function test_explicit_daily_modes_share_the_same_log_service_without_context_duplication(): void
    {
        $context = $this->context();
        [$outbound, $outboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-MODE-OUT',
            '100.000000',
            '10.000000',
            '20.000000',
            '30.000000',
        );
        [$inbound, $inboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Inbound,
            'AGR-MODE-IN',
            '50.000000',
            '5.000000',
            '10.000000',
            '15.000000',
            false,
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
        app(RentalAgreementVehicleLinkService::class)->submit($link, null);
        app(RentalAgreementVehicleLinkService::class)->approve($link->refresh(), null);
        app(RentalAgreementService::class)->changeStatus($inbound->refresh(), RentalAgreementStatus::Active);
        $service = app(RentalUsageLogService::class);

        $lesseeOnly = $service->createForMode(
            RentalUsageContextService::MODE_LESSEE,
            $outbound->refresh(),
            new RentalUsageLogData(
                agreementVehicleId: (int) $outboundAllocation->getKey(),
                usageDate: '2026-06-02',
                startOdometer: '1000.000000',
                endOdometer: '1010.000000',
                startTime: '08:00',
                endTime: '09:00',
            ),
        );
        $lessorOnly = $service->createForMode(
            RentalUsageContextService::MODE_LESSOR,
            $inbound->refresh(),
            new RentalUsageLogData(
                agreementVehicleId: (int) $inboundAllocation->getKey(),
                usageDate: '2026-06-02',
                startOdometer: '1010.000000',
                endOdometer: '1020.000000',
                startTime: '10:00',
                endTime: '11:00',
            ),
        );
        $linked = $service->createForMode(
            RentalUsageContextService::MODE_LINKED,
            $outbound->refresh(),
            new RentalUsageLogData(
                agreementVehicleId: (int) $outboundAllocation->getKey(),
                usageDate: '2026-06-02',
                startOdometer: '1020.000000',
                endOdometer: '1030.000000',
                startTime: '12:00',
                endTime: '13:00',
            ),
            $inbound->refresh(),
            (int) $inboundAllocation->getKey(),
        );

        $this->assertDatabaseCount('rental_usage_logs', 3);
        $this->assertCount(1, $lesseeOnly->contexts);
        $this->assertSame('revenue', $lesseeOnly->contexts->sole()->financial_side->value);
        $this->assertCount(1, $lessorOnly->contexts);
        $this->assertSame('cost', $lessorOnly->contexts->sole()->financial_side->value);
        $this->assertCount(2, $linked->contexts);
        $this->assertSame(
            ['cost', 'revenue'],
            $linked->contexts()->orderBy('financial_side')->get()
                ->map(fn ($usageContext): string => $usageContext->financial_side->value)
                ->all(),
        );
    }

    public function test_duplicate_daily_save_is_idempotent_and_overlapping_times_are_rejected(): void
    {
        $context = $this->context();
        [$agreement, $allocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-DUPLICATE-OVERLAP',
            '100.000000',
            '10.000000',
            '20.000000',
            '30.000000',
        );
        $service = app(RentalUsageLogService::class);
        $data = new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1000.000000',
            endOdometer: '1010.000000',
            startTime: '08:00',
            endTime: '10:00',
        );
        $first = $service->createForMode(RentalUsageContextService::MODE_LESSEE, $agreement, $data);
        $duplicate = $service->createForMode(RentalUsageContextService::MODE_LESSEE, $agreement->refresh(), $data);

        $this->assertSame((int) $first->getKey(), (int) $duplicate->getKey());
        $this->assertDatabaseCount('rental_usage_logs', 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('overlaps an existing running chart');
        $service->createForMode(
            RentalUsageContextService::MODE_LESSEE,
            $agreement->refresh(),
            new RentalUsageLogData(
                agreementVehicleId: (int) $allocation->getKey(),
                usageDate: '2026-06-02',
                startOdometer: '1010.000000',
                endOdometer: '1020.000000',
                startTime: '09:30',
                endTime: '11:00',
            ),
        );
    }

    public function test_daily_submit_rolls_back_all_rows_when_one_trip_fails(): void
    {
        $context = $this->context();
        [$agreement, $allocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-DAILY-ROLLBACK',
            '100.000000',
            '10.000000',
            '20.000000',
            '30.000000',
        );
        $this->actingAs(
            $this->runningChartUser($context['tenant_id'], $context['organization_unit_id']),
            (string) config('module-auth.protected_route_guard', 'auth-api'),
        );

        try {
            $this->withoutExceptionHandling()
                ->withoutMiddleware()
                ->postJson('/api/v1/vehicle-rental/running-chart/daily-submit', [
                    'tenant_id' => $context['tenant_id'],
                    'organization_unit_id' => $context['organization_unit_id'],
                    'mode' => RentalUsageContextService::MODE_LESSEE,
                    'lessee_agreement_id' => $agreement->getKey(),
                    'lessee_agreement_vehicle_id' => $allocation->getKey(),
                    'usage_date' => '2026-06-02',
                    'trips' => [[
                        'start_time' => '08:00',
                        'end_time' => '10:00',
                        'start_odometer' => '1000.000000',
                        'end_odometer' => '1010.000000',
                    ], [
                        'start_time' => '09:00',
                        'end_time' => '11:00',
                        'start_odometer' => '1010.000000',
                        'end_odometer' => '1020.000000',
                    ]],
                ]);
            $this->fail('Expected overlapping daily submit rows to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('overlaps an existing running chart', $exception->getMessage());
        }

        $this->assertDatabaseCount('rental_usage_logs', 0);
        $this->assertDatabaseCount('rental_usage_contexts', 0);
        $this->assertDatabaseCount('rental_usage_events', 0);
    }

    public function test_no_usage_preview_does_not_persist_fake_daily_records(): void
    {
        $context = $this->context();
        [$agreement, $allocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-NO-USAGE',
            '100.000000',
            '10.000000',
            '20.000000',
            '30.000000',
        );
        $resolved = app(RentalUsageContextService::class)->resolveForMode(
            RentalUsageContextService::MODE_LESSEE,
            $agreement,
            $allocation,
            '2026-06-02',
        );
        $preview = app(RentalChargeCalculationService::class)->previewRunningChart($resolved, []);

        $this->assertSame('0.000000', $preview['daily_km']);
        $this->assertSame('0.000000', $preview['customer_revenue']);
        $this->assertDatabaseCount('rental_usage_logs', 0);
        $this->assertDatabaseCount('rental_usage_contexts', 0);
    }

    public function test_running_chart_preview_is_non_persistent_and_uses_each_context_snapshot(): void
    {
        $context = $this->context();
        [$outbound, $outboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-PREVIEW-OUT',
            '100.000000',
            '10.000000',
            '20.000000',
            '30.000000',
        );
        [$inbound, $inboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Inbound,
            'AGR-PREVIEW-IN',
            '50.000000',
            '5.000000',
            '10.000000',
            '15.000000',
            false,
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
        app(RentalAgreementVehicleLinkService::class)->submit($link, null);
        app(RentalAgreementVehicleLinkService::class)->approve($link->refresh(), null);
        app(RentalAgreementService::class)->changeStatus($inbound->refresh(), RentalAgreementStatus::Active);
        $resolved = app(RentalUsageContextService::class)->resolveForMode(
            RentalUsageContextService::MODE_LINKED,
            $outbound,
            $outboundAllocation,
            '2026-06-02',
            '08:00',
            $inbound->refresh(),
            $inboundAllocation->refresh(),
        );
        $preview = app(RentalChargeCalculationService::class)->previewRunningChart($resolved, [[
            'usage_date' => '2026-06-02',
            'start_time' => '08:00',
            'end_time' => '13:00',
            'start_odometer' => '1000.000000',
            'end_odometer' => '1100.000000',
            'events' => [[
                'event_type' => RentalUsageEventType::Overtime->value,
                'quantity' => '2.000000',
            ]],
        ]]);

        $this->assertSame('100.000000', $preview['daily_km']);
        $this->assertSame('640.000000', $preview['customer_revenue']);
        $this->assertSame('320.000000', $preview['owner_cost']);
        $this->assertSame('320.000000', $preview['estimated_margin']);
        $this->assertDatabaseCount('rental_charge_calculations', 0);
        $this->assertDatabaseCount('rental_charges', 0);
    }

    public function test_link_requires_submission_and_approval_before_it_can_add_a_counterpart_context(): void
    {
        $context = $this->context();
        [$outbound, $outboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-LINK-LIFECYCLE-OUT',
            '100.000000',
            '10.000000',
            '20.000000',
            '30.000000',
        );
        [$inbound, $inboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Inbound,
            'AGR-LINK-LIFECYCLE-IN',
            '50.000000',
            '5.000000',
            '10.000000',
            '15.000000',
            false,
        );
        $service = app(RentalAgreementVehicleLinkService::class);
        $link = $service->create(
            $context['tenant_id'],
            $context['organization_unit_id'],
            new RentalAgreementVehicleLinkData(
                inboundAgreementVehicleId: (int) $inboundAllocation->getKey(),
                outboundAgreementVehicleId: (int) $outboundAllocation->getKey(),
                effectiveFrom: '2026-06-01 08:00:00',
                effectiveTo: '2026-06-03 08:00:00',
            ),
        );

        $this->assertSame('draft', $link->status->value);
        $this->assertNull($link->approved_at);
        $this->assertCount(1, app(RentalUsageContextService::class)
            ->resolve($outbound, $outboundAllocation, '2026-06-02', '09:00')['contexts']);

        $link = $service->submit($link, 11, 'Validated allocation pairing.');
        $this->assertSame('submitted', $link->status->value);
        $this->assertCount(1, app(RentalUsageContextService::class)
            ->resolve($outbound, $outboundAllocation, '2026-06-02', '09:00')['contexts']);

        $link = $service->approve($link, 12, 'Authorised pairing.');
        app(RentalAgreementService::class)->changeStatus($inbound->refresh(), RentalAgreementStatus::Active);
        $resolved = app(RentalUsageContextService::class)
            ->resolve($outbound, $outboundAllocation, '2026-06-02', '09:00');
        $this->assertSame('approved', $link->status->value);
        $this->assertCount(2, $resolved['contexts']);
        $this->assertSame(3, DB::table('rental_status_histories')
            ->where('entity_type', 'vehicle_link')
            ->where('subject_id', $link->getKey())
            ->count());

        $usage = app(RentalUsageLogService::class)->create($outbound, new RentalUsageLogData(
            agreementVehicleId: (int) $outboundAllocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1000.000000',
            endOdometer: '1010.000000',
            startTime: '09:00',
            endTime: '10:00',
        ));
        $this->assertCount(2, $usage->contexts);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('used by a running chart');
        $service->cancel($link, 12, 'Invalid cancellation attempt.');
    }

    public function test_future_and_cancelled_links_are_ignored_and_ambiguous_links_are_controlled(): void
    {
        $context = $this->context();
        [$outbound, $outboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-LINK-TIME-OUT',
            '100.000000',
            '10.000000',
            '20.000000',
            '30.000000',
        );
        [$inbound, $inboundAllocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Inbound,
            'AGR-LINK-TIME-IN',
            '50.000000',
            '5.000000',
            '10.000000',
            '15.000000',
            false,
        );
        $service = app(RentalAgreementVehicleLinkService::class);
        $future = $service->create(
            $context['tenant_id'],
            $context['organization_unit_id'],
            new RentalAgreementVehicleLinkData(
                inboundAgreementVehicleId: (int) $inboundAllocation->getKey(),
                outboundAgreementVehicleId: (int) $outboundAllocation->getKey(),
                effectiveFrom: '2026-06-02 12:00:00',
                effectiveTo: '2026-06-03 08:00:00',
            ),
        );
        $future = $service->submit($future, null);
        $future = $service->approve($future, null);
        $resolver = app(RentalUsageContextService::class);

        $this->assertCount(1, $resolver
            ->resolve($outbound, $outboundAllocation, '2026-06-02', '09:00')['contexts']);
        $service->cancel($future, null, 'Pairing withdrawn before use.');
        $this->assertCount(1, $resolver
            ->resolve($outbound, $outboundAllocation, '2026-06-02', '13:00')['contexts']);

        $attributes = [
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'vehicle_id' => $context['vehicle_id'],
            'inbound_agreement_id' => $inbound->getKey(),
            'inbound_agreement_vehicle_id' => $inboundAllocation->getKey(),
            'outbound_agreement_id' => $outbound->getKey(),
            'outbound_agreement_vehicle_id' => $outboundAllocation->getKey(),
            'effective_to' => '2026-06-03 08:00:00',
            'status' => 'approved',
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        DB::table('rental_agreement_vehicle_links')->insert([
            [...$attributes, 'effective_from' => '2026-06-01 08:00:00'],
            [...$attributes, 'effective_from' => '2026-06-01 09:00:00'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ambiguous');
        $resolver->resolve($outbound, $outboundAllocation, '2026-06-02', '10:00');
    }

    public function test_backdated_mileage_checks_next_neighbour_and_rebuilds_cumulative_chain(): void
    {
        $context = $this->context();
        [$agreement, $allocation] = $this->activeAgreement(
            $context,
            RentalAgreementDirection::Outbound,
            'AGR-BACKDATED',
            '100.000000',
            '10.000000',
            '20.000000',
            '30.000000',
        );
        $service = app(RentalUsageLogService::class);
        $first = $this->approveUsage($service, $agreement, $allocation, '09:00', '1000.000000', '1010.000000');
        $later = $service->create($agreement, new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1015.000000',
            endOdometer: '1020.000000',
            startTime: '12:00',
            endTime: '13:00',
        ));
        $service->changeStatus($later, RentalUsageLogStatus::Submitted);
        $later = $service->changeStatus(
            $later->refresh(),
            RentalUsageLogStatus::Approved,
            reason: 'Temporary documented mileage gap pending backdated chart.',
            allowMileageVariance: true,
        );
        $middle = $this->approveUsage(
            $service,
            $agreement,
            $allocation,
            '10:15',
            '1010.000000',
            '1015.000000',
        );

        $this->assertSame('10.000000', (string) $first->cumulative_km);
        $this->assertSame('15.000000', (string) $middle->cumulative_km);
        $this->assertSame('20.000000', (string) $later->refresh()->cumulative_km);

        $invalid = $service->create($agreement, new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: '1010.000000',
            endOdometer: '1011.000000',
            startTime: '10:00',
            endTime: '10:10',
        ));
        $service->changeStatus($invalid, RentalUsageLogStatus::Submitted);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('next approved start odometer');
        $service->changeStatus($invalid->refresh(), RentalUsageLogStatus::Approved);
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
        bool $activate = true,
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
        if ($activate) {
            app(RentalAgreementService::class)->changeStatus(
                $agreement->refresh(),
                RentalAgreementStatus::Active,
            );
        }

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

    private function approveUsage(
        RentalUsageLogService $service,
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
        string $startTime,
        string $startOdometer,
        string $endOdometer,
    ): RentalUsageLog {
        $usage = $service->create($agreement, new RentalUsageLogData(
            agreementVehicleId: (int) $allocation->getKey(),
            usageDate: '2026-06-02',
            startOdometer: $startOdometer,
            endOdometer: $endOdometer,
            startTime: $startTime,
            endTime: CarbonImmutable::parse('2026-06-02 '.$startTime)->addHour()->format('H:i'),
        ));
        $service->changeStatus($usage, RentalUsageLogStatus::Submitted);

        return $service->changeStatus($usage->refresh(), RentalUsageLogStatus::Approved);
    }

    private function runningChartUser(int $tenantId, int $organizationUnitId): UserModel
    {
        $user = UserModel::query()->create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'first_name' => 'Running',
            'last_name' => 'Chart',
            'email' => 'running-chart-'.$tenantId.'@example.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $permissionId = (int) DB::table('permissions')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'name' => VehicleRentalAuthorizationService::RECORD_USAGE,
            'guard_name' => (string) config('auth.defaults.guard', 'api'),
            'module' => 'vehicle-rental',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_permissions')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $user->getKey(),
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
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
