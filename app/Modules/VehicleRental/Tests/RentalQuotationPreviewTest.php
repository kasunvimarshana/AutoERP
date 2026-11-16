<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Invoice\Models\Invoice;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Tests\TestCase;

class RentalQuotationPreviewTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_customer_quotation_preview_returns_accurate_calculations_and_zero_db_rows_persisted(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(
            array_keys(RentalAuthorization::descriptions()),
            [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]
        );

        $agreementId = null;
        $invoicesBefore = 0;
        $invoiceLinesBefore = 0;
        $cyclesBefore = 0;
        $cycleItemsBefore = 0;
        $cycleChartsBefore = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$agreementId, &$invoicesBefore, &$invoiceLinesBefore, &$cyclesBefore, &$cycleItemsBefore, &$cycleChartsBefore): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms'] = [
                'base_rate' => '60000.000000',
                'included_km' => '100.000000',
                'excess_km_rate' => '50.000000',
                'normal_ot_rate' => '300.000000',
                'double_ot_rate' => '400.000000',
                'triple_ot_rate' => '600.000000',
                'night_out_rate' => '1500.000000',
            ];

            $agreements = app(AgreementService::class);
            $agreement = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $agreements->change(AgreementKind::Customer, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);
            $agreementId = $agreement->id;

            $invoicesBefore = DB::table('invoices')->count();
            $invoiceLinesBefore = DB::table('invoice_lines')->count();
            $cyclesBefore = DB::table('vehicle_rental_billing_cycles')->count();
            $cycleItemsBefore = DB::table('vehicle_rental_billing_cycle_items')->count();
            $cycleChartsBefore = DB::table('vehicle_rental_billing_cycle_charts')->count();
        });

        $payload = [
            'agreement_id' => $agreementId,
            'period_from' => '2026-09-01',
            'period_until' => '2026-09-30',
            'custody_days' => 30,
            'excess_km' => '50.000000',
            'normal_ot_hours' => '10.000000',
            'double_ot_hours' => '5.000000',
            'triple_ot_hours' => '2.000000',
            'night_outs' => 3,
            'tolls_amount' => '1200.000000',
            'additions' => [
                ['description' => 'Airport Surcharge', 'amount' => '2500.000000'],
            ],
            'deductions' => [
                ['description' => 'Early Booking Discount', 'amount' => '3000.000000'],
            ],
            'tax_rate' => '10.000000',
        ];

        // Call HTTP endpoint outside withTenantExecutionContext
        $response = $this->postJson('/api/v1/vehicle-rental/billing/quotation-preview', $payload);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertNotNull($data);
        $this->assertTrue($data['is_preview']);
        $this->assertFalse($data['persisted']);
        $this->assertStringStartsWith('QUOT-', $data['quotation_number']);

        $breakdown = $data['breakdown'] ?? $data['financial_breakdown'];
        $this->assertEqualsWithDelta(60000.0, (float) ($breakdown['base_rent_amount'] ?? $breakdown['base_rent']), 0.01);
        $this->assertEqualsWithDelta(2500.0, (float) ($breakdown['excess_km_amount'] ?? $breakdown['excess_km']), 0.01); // 50 km * 50
        $this->assertEqualsWithDelta(3000.0, (float) $breakdown['normal_ot_amount'], 0.01); // 10 hrs * 300
        $this->assertEqualsWithDelta(2000.0, (float) $breakdown['double_ot_amount'], 0.01); // 5 hrs * 400
        $this->assertEqualsWithDelta(1200.0, (float) $breakdown['triple_ot_amount'], 0.01); // 2 hrs * 600
        $this->assertEqualsWithDelta(6200.0, (float) ($breakdown['overtime_amount'] ?? $breakdown['overtime']), 0.01); // 3000 + 2000 + 1200
        $this->assertEqualsWithDelta(4500.0, (float) ($breakdown['night_out_amount'] ?? $breakdown['night_out']), 0.01); // 3 * 1500
        $this->assertEqualsWithDelta(1200.0, (float) ($breakdown['tolls_amount'] ?? $breakdown['tolls']), 0.01);
        $this->assertEqualsWithDelta(2500.0, (float) ($breakdown['additions_amount'] ?? $breakdown['additions']), 0.01);
        $this->assertEqualsWithDelta(3000.0, (float) ($breakdown['deductions_amount'] ?? $breakdown['deductions']), 0.01);

        // Gross = 60000 + 2500 + 6200 + 4500 + 1200 + 2500 = 76900
        // Net Subtotal = 76900 - 3000 = 73900
        $this->assertEqualsWithDelta(73900.0, (float) $breakdown['net_subtotal'], 0.01);
        // Tax (10%) = 7390
        $this->assertEqualsWithDelta(7390.0, (float) $breakdown['tax_amount'], 0.01);
        // Grand Total = 73900 + 7390 = 81290
        $this->assertEqualsWithDelta(81290.0, (float) $breakdown['grand_total'], 0.01);

        // CRITICAL VERIFICATION: Zero rows persisted in the database!
        $this->assertSame($invoicesBefore, DB::table('invoices')->count(), 'Invoices count must not change on preview');
        $this->assertSame($invoiceLinesBefore, DB::table('invoice_lines')->count(), 'Invoice lines count must not change on preview');
        $this->assertSame($cyclesBefore, DB::table('vehicle_rental_billing_cycles')->count(), 'Billing cycles count must not change on preview');
        $this->assertSame($cycleItemsBefore, DB::table('vehicle_rental_billing_cycle_items')->count(), 'Billing cycle items count must not change on preview');
        $this->assertSame($cycleChartsBefore, DB::table('vehicle_rental_billing_cycle_charts')->count(), 'Billing cycle charts count must not change on preview');
    }

    public function test_owner_bill_preview_simulates_settlement_and_persists_zero_db_rows(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(
            array_keys(RentalAuthorization::descriptions()),
            [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]
        );

        $ownerAgId = null;
        $invoicesBefore = 0;
        $invoiceLinesBefore = 0;
        $cyclesBefore = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData, &$ownerAgId, &$invoicesBefore, &$invoiceLinesBefore, &$cyclesBefore): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = [
                'base_rate' => '50000.000000',
            ];

            $agreements = app(AgreementService::class);
            $agreement = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $agreements->change(AgreementKind::Owner, $context, $agreement->id, $agreement->row_version, AgreementAction::Activate);
            $ownerAgId = $agreement->id;

            $invoicesBefore = DB::table('invoices')->count();
            $invoiceLinesBefore = DB::table('invoice_lines')->count();
            $cyclesBefore = DB::table('vehicle_rental_billing_cycles')->count();
        });

        $payload = [
            'owner_agreement_id' => $ownerAgId,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'wht_rate' => 10,
        ];

        // Call HTTP endpoint outside withTenantExecutionContext
        $response = $this->postJson('/api/v1/vehicle-rental/billing/owner-bill-preview', $payload);

        $response->assertOk();
        $data = $response->json('data');

        $this->assertNotNull($data);
        $this->assertTrue($data['is_preview']);
        $this->assertFalse($data['persisted']);

        $breakdown = $data['breakdown'] ?? $data['financial_breakdown'];
        $this->assertEqualsWithDelta(50000.0, (float) $breakdown['gross_payable'], 0.01);
        $this->assertEqualsWithDelta(5000.0, (float) $breakdown['wht_amount'], 0.01); // 10% statutory WHT
        $this->assertEqualsWithDelta(45000.0, (float) $breakdown['net_payable'], 0.01);

        // CRITICAL VERIFICATION: Zero rows persisted!
        $this->assertSame($invoicesBefore, DB::table('invoices')->count(), 'Invoices count must not change on owner bill preview');
        $this->assertSame($invoiceLinesBefore, DB::table('invoice_lines')->count(), 'Invoice lines count must not change on owner bill preview');
        $this->assertSame($cyclesBefore, DB::table('vehicle_rental_billing_cycles')->count(), 'Billing cycles count must not change on owner bill preview');
    }
}
