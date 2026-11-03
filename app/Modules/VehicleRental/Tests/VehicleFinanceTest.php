<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Tenancy\TenantFeature;
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

    public function test_vehicle_finance_api_endpoints(): void
    {
        [$context, , $ownerData] = $this->loginFixture(modules: [TenantFeature::VEHICLE_RENTAL, TenantFeature::INVOICE]);

        // 1. Index agreements returns 200 without requiring POST fields
        $this->getJson($this->rentalRoot.'/finance-agreements')
            ->assertOk()
            ->assertJsonPath('data', []);

        // 2. Store finance agreement
        $createResponse = $this->postJson($this->rentalRoot.'/finance-agreements', [
            'agreement_number' => 'VFA-API-001',
            'supplier_id' => $ownerData['party_id'],
            'vehicle_id' => $ownerData['vehicle_id'],
            'agreement_date' => '2026-02-01',
            'starts_at' => '2026-02-01T00:00:00+05:30',
            'matures_at' => '2027-02-01T00:00:00+05:30',
            'currency_id' => $ownerData['currency_id'],
            'principal_amount' => '600000.000000',
            'initial_deposit_amount' => '100000.000000',
            'annual_interest_rate' => '10.000000',
            'installment_frequency' => 'monthly',
            'installment_count' => 6,
        ])->assertCreated();

        $agreementId = $createResponse->json('data.id');
        $version = $createResponse->json('data.row_version');

        // 3. Show agreement
        $showResponse = $this->getJson($this->rentalRoot.'/finance-agreements/'.$agreementId)
            ->assertOk()
            ->assertJsonPath('data.id', $agreementId)
            ->assertJsonPath('data.status', 'draft');

        $installmentId = $showResponse->json('data.installments.0.id');
        $installmentVersion = $showResponse->json('data.installments.0.row_version');

        // 4. Activate agreement
        $this->patchJson($this->rentalRoot.'/finance-agreements/'.$agreementId.'/activate', [
            'expected_version' => $version,
        ])->assertOk()->assertJsonPath('data.status', 'active');

        // 5. Create payable invoice for installment
        $this->postJson($this->rentalRoot.'/finance-agreements/installments/'.$installmentId.'/payable', [
            'expected_version' => $installmentVersion,
            'invoice_date' => '2026-02-15',
        ])->assertCreated()->assertJsonPath('data.direction', 'inbound');

        // 6. Refresh due statuses
        $this->patchJson($this->rentalRoot.'/finance-agreements/installments/refresh-due-statuses')
            ->assertOk()
            ->assertJsonStructure(['updated']);

        // 7. Test cancelling a draft agreement with custom schedule
        $customDraft = $this->postJson($this->rentalRoot.'/finance-agreements', [
            'agreement_number' => 'VFA-CUSTOM-001',
            'supplier_id' => $ownerData['party_id'],
            'vehicle_id' => $ownerData['vehicle_id'],
            'agreement_date' => '2026-03-01',
            'starts_at' => '2026-03-01T00:00:00+05:30',
            'matures_at' => '2026-05-01T00:00:00+05:30',
            'currency_id' => $ownerData['currency_id'],
            'principal_amount' => '200000.000000',
            'annual_interest_rate' => '10.000000',
            'installment_frequency' => 'monthly',
            'installment_count' => 2,
            'schedule' => [
                [
                    'installment_number' => 1,
                    'due_date' => '2026-04-01',
                    'principal_due' => '100000.000000',
                    'interest_due' => '10000.000000',
                ],
                [
                    'installment_number' => 2,
                    'due_date' => '2026-05-01',
                    'principal_due' => '100000.000000',
                    'interest_due' => '5000.000000',
                ],
            ],
        ])->assertCreated()->json('data');

        $this->assertCount(2, $customDraft['installments']);
        $this->assertSame('100000.000000', $customDraft['installments'][0]['principal_due']);
        $this->assertSame('10000.000000', $customDraft['installments'][0]['interest_due']);

        // Cancel the draft agreement
        $this->patchJson($this->rentalRoot.'/finance-agreements/'.$customDraft['id'].'/cancel', [
            'expected_version' => $customDraft['row_version'],
            'reason' => 'Duplicate agreement entered in error',
        ])->assertOk()->assertJsonPath('data.status', 'cancelled');
    }
}
