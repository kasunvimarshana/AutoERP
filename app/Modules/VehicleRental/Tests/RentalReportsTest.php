<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Vehicle\Enums\VehicleDocumentStatus;
use Modules\Vehicle\Enums\VehicleDocumentType;
use Modules\Vehicle\Models\VehicleDocument;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Services\FleetUtilizationReportService;
use Modules\VehicleRental\Services\LesseeOutstandingReportService;
use Modules\VehicleRental\Services\OwnerDisbursementReportService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalIncidentReportService;
use Modules\VehicleRental\Services\RentalRevenueReportService;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\VehicleProfitabilityReportService;
use Modules\VehicleRental\Services\VehicleRevenueLicenseReportService;
use Tests\Support\ActiveTenantSubscriptionFixture;
use Tests\TestCase;

final class RentalReportsTest extends TestCase
{
    use BuildsRentalFixture, RefreshDatabase;

    private const ROOT = '/api/v1/vehicle-rental';

    public function test_fleet_utilization_report_generates_kpis_and_exports(): void
    {
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assertReport')->zeroOrMoreTimes();
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
            $mock->shouldReceive('assertChart')->zeroOrMoreTimes();
        });

        $this->custodyFixture(function ($context, $use, $facts): void {
            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, (int) $use->id, (int) $use->row_version, $facts);
            $charts->change($context, (int) $chart->id, (int) $chart->row_version, RunningChartAction::Finalize);

            $service = app(FleetUtilizationReportService::class);
            $result = $service->generate($context, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'ownership_type' => 'all',
                'custody_status' => 'all',
            ]);

            self::assertArrayHasKey('header', $result);
            self::assertArrayHasKey('summary', $result);
            self::assertArrayHasKey('rows', $result);
            self::assertGreaterThanOrEqual(1, $result['summary']['total_vehicles']);
            self::assertGreaterThanOrEqual(1, count($result['rows']));

            $row = $result['rows'][0];
            self::assertArrayHasKey('vehicle_id', $row);
            self::assertArrayHasKey('registration_number', $row);
            self::assertArrayHasKey('utilization_rate', $row);
            self::assertArrayHasKey('custody_status', $row);
        });
    }

    public function test_vehicle_revenue_license_report_generates_and_detects_expiry(): void
    {
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assertReport')->zeroOrMoreTimes();
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
        });

        $this->custodyFixture(function ($context, $use): void {
            // Attach a revenue license document to the vehicle
            VehicleDocument::create([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'vehicle_id' => $use->vehicle_id,
                'document_type' => VehicleDocumentType::RevenueLicense,
                'document_number' => 'RL-2026-8899',
                'issued_date' => '2025-05-01',
                'expiry_date' => '2026-05-01',
                'status' => VehicleDocumentStatus::Active,
            ]);

            $service = app(VehicleRevenueLicenseReportService::class);
            $result = $service->generate($context, [
                'status' => 'all',
            ]);

            self::assertArrayHasKey('header', $result);
            self::assertArrayHasKey('summary', $result);
            self::assertArrayHasKey('data', $result);

            $matched = collect($result['data'])->firstWhere('vehicle_id', $use->vehicle_id);
            self::assertNotNull($matched);
            self::assertSame('01/05/2026', $matched['expire_date']);
            self::assertSame('RL-2026-8899', $matched['document_number']);
        });
    }

    public function test_rental_revenue_report_aggregates_billings(): void
    {
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assertReport')->zeroOrMoreTimes();
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
        });

        $this->custodyFixture(function ($context, $use): void {
            $service = app(RentalRevenueReportService::class);
            $result = $service->generate($context, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
            ]);

            self::assertArrayHasKey('header', $result);
            self::assertArrayHasKey('summary', $result);
            self::assertArrayHasKey('rows', $result);
            self::assertGreaterThanOrEqual(1, $result['summary']['total_agreements']);
        });
    }

    public function test_lessee_outstanding_report_groups_by_customer(): void
    {
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assertReport')->zeroOrMoreTimes();
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
        });

        $this->custodyFixture(function ($context, $use): void {
            // Insert an outstanding invoice linked to the customer agreement
            $invoiceId = DB::table('invoices')->insertGetId([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'party_type' => 'customer',
                'party_id' => $use->customerAgreement->customer_id,
                'invoice_number' => 'INV-TEST-001',
                'invoice_type' => 'standard',
                'direction' => 'outbound',
                'invoice_date' => '2026-09-10',
                'due_date' => '2026-09-25',
                'subtotal' => 50000.00,
                'tax_total' => 0.00,
                'discount_total' => 0.00,
                'grand_total' => 50000.00,
                'status' => 'issued',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('invoice_balances')->insert([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'invoice_id' => $invoiceId,
                'invoice_total' => 50000.00,
                'paid_amount' => 10000.00,
                'remaining_amount' => 40000.00,
                'status' => 'partially_paid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('invoice_sources')->insert([
                'tenant_id' => $context->tenantId,
                'organization_unit_id' => $context->organizationUnitId,
                'invoice_id' => $invoiceId,
                'source_type' => 'base_rent',
                'source_id' => 1,
                'source_document_number' => $use->customerAgreement->reference,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $service = app(LesseeOutstandingReportService::class);
            $result = $service->generate($context, [
                'as_of_date' => '2026-10-01',
                'aging_bucket' => 'all',
            ]);

            self::assertArrayHasKey('header', $result);
            self::assertArrayHasKey('summary', $result);
            self::assertArrayHasKey('groups', $result);
            self::assertGreaterThanOrEqual(1, $result['summary']['total_lessees_count']);

            $group = $result['groups'][0];
            self::assertSame($use->customerAgreement->customer_id, $group['customer_id']);
            self::assertCount(1, $group['invoices']);
            self::assertSame('INV-TEST-001', $group['invoices'][0]['invoice_number']);
            self::assertSame('40000.00', $group['invoices'][0]['balance_amount']);
        });
    }

    public function test_owner_disbursement_and_profitability_reports(): void
    {
        $this->mock(RentalAuthorization::class, function ($mock): void {
            $mock->shouldReceive('assertReport')->zeroOrMoreTimes();
            $mock->shouldReceive('assert')->zeroOrMoreTimes();
            $mock->shouldReceive('assertUse')->zeroOrMoreTimes();
        });

        $this->custodyFixture(function ($context, $use): void {
            $ownerService = app(OwnerDisbursementReportService::class);
            $ownerData = $ownerService->generate($context, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => 10.0,
            ]);

            self::assertArrayHasKey('header', $ownerData);
            self::assertArrayHasKey('summary', $ownerData);
            self::assertArrayHasKey('rows', $ownerData);
            self::assertArrayHasKey('wht_ledger', $ownerData);
            self::assertArrayHasKey('total_gross_payable', $ownerData['summary']);
            self::assertArrayHasKey('total_wht_amount', $ownerData['summary']);

            $whtLedgerData = $ownerService->generate($context, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'report_type' => 'wht_ledger',
                'wht_rate' => 10.0,
            ]);
            self::assertSame('wht_ledger', $whtLedgerData['header']['report_type']);
            self::assertSame(':3305-000 WHT', $whtLedgerData['header']['account_code']);

            $profitService = app(VehicleProfitabilityReportService::class);
            $profitData = $profitService->generate($context, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'ownership_type' => 'all',
            ]);

            self::assertArrayHasKey('header', $profitData);
            self::assertArrayHasKey('summary', $profitData);
            self::assertArrayHasKey('rows', $profitData);
        });
    }

    public function test_reports_http_endpoints_and_print_exports(): void
    {
        [$context, $customer, $owner] = $this->loginFixture();

        // Test Fleet Utilization endpoint & print preview
        $response = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->getJson(self::ROOT.'/reports/fleet-utilization?start_date=2026-09-01&end_date=2026-09-30');
        $response->assertOk()->assertJsonStructure(['header', 'summary', 'rows']);

        $printResp = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->get(self::ROOT.'/reports/fleet-utilization/export/print?start_date=2026-09-01&end_date=2026-09-30');
        $printResp->assertOk()->assertSee('FLEET UTILIZATION', false);

        // Test Revenue Licenses endpoint & print preview
        $licResp = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->getJson(self::ROOT.'/reports/revenue-licenses?status=all');
        $licResp->assertOk()->assertJsonStructure(['header', 'summary', 'data']);

        $licPrint = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->get(self::ROOT.'/reports/revenue-licenses/export/print?status=all');
        $licPrint->assertOk()->assertSee('Vehicle Revenue Licence Expire Report', false);

        // Test Rental Revenue endpoint & print preview
        $revResp = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->getJson(self::ROOT.'/reports/rental-revenue?start_date=2026-09-01&end_date=2026-09-30');
        $revResp->assertOk()->assertJsonStructure(['header', 'summary', 'rows']);

        $revPrint = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->get(self::ROOT.'/reports/rental-revenue/export/print?start_date=2026-09-01&end_date=2026-09-30');
        $revPrint->assertOk()->assertSee('RENTAL REVENUE', false);

        // Test Lessee Outstanding endpoint & print preview
        $outResp = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->getJson(self::ROOT.'/reports/lessee-outstanding?as_of_date=2026-09-30');
        $outResp->assertOk()->assertJsonStructure(['header', 'summary', 'groups']);

        $outPrint = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->get(self::ROOT.'/reports/lessee-outstanding/export/print?as_of_date=2026-09-30');
        $outPrint->assertOk()->assertSee('Outstanding', false);

        // Test Owner Disbursement endpoint & WHT ledger print preview
        $ownerResp = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->getJson(self::ROOT.'/reports/owner-disbursements?start_date=2026-09-01&end_date=2026-09-30&wht_rate=10');
        $ownerResp->assertOk()->assertJsonStructure(['header', 'summary', 'rows', 'wht_ledger']);

        $ownerWhtPrint = $this->withHeader('X-Tenant-Id', (string) $context->tenantId)
            ->get(self::ROOT.'/reports/owner-disbursements/export/print?start_date=2026-09-01&end_date=2026-09-30&report_type=wht_ledger&wht_rate=10');
        $ownerWhtPrint->assertOk()->assertSee('3305-000 WHT', false);
    }
}
