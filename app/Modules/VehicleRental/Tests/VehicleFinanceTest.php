<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceType;
use Modules\VehicleRental\Enums\VehicleFinanceAgreementStatus;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentStatus;
use Modules\VehicleRental\Services\VehicleFinanceService;
use Tests\TestCase;

final class VehicleFinanceTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_vehicle_finance_agreement_schedule_and_payable(): void
    {
        [$context, , $ownerData] = $this->fixture();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData): void {
            $service = app(VehicleFinanceService::class);

            $agreement = $service->create([
                'agreement_number' => 'VFA-TEST-001',
                'supplier_id' => $ownerData['party_id'],
                'vehicle_id' => $ownerData['vehicle_id'],
                'agreement_date' => '2026-01-01',
                'starts_at' => '2026-01-01T00:00:00+05:30',
                'matures_at' => '2027-01-01T00:00:00+05:30',
                'currency_id' => $ownerData['currency_id'],
                'principal_amount' => '1200000.000000',
                'initial_deposit_amount' => '200000.000000',
                'annual_interest_rate' => '12.000000',
                'installment_frequency' => 'monthly',
                'installment_count' => 12,
            ], $context->tenantId, $context->organizationUnitId, $context->actorId);

            $this->assertSame(VehicleFinanceAgreementStatus::Draft, $agreement->status);
            $this->assertCount(12, $agreement->installments);

            // Test first installment calculation
            $first = $agreement->installments->first();
            $this->assertSame(1, $first->installment_number);
            $this->assertSame(VehicleFinanceInstallmentStatus::Scheduled, $first->status);
            $this->assertGreaterThan(0, (float) $first->total_due);

            // Activate agreement
            $activated = $service->activate($agreement, $agreement->row_version, $context->actorId);
            $this->assertSame(VehicleFinanceAgreementStatus::Active, $activated->status);

            // Generate payable invoice
            $invoice = $service->createPayableInvoice($first, '2026-01-15', $first->row_version, $context->actorId);
            $this->assertNotNull($invoice);
            $this->assertSame(InvoiceType::Rental, $invoice->invoice_type);
            $this->assertSame(InvoiceDirection::Inbound, $invoice->direction);

            $first->refresh();
            $this->assertSame($invoice->id, $first->invoice_id);
        });
    }
}
