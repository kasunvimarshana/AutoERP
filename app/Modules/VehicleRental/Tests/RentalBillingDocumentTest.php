<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Tenancy\TenantFeature;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Invoice\Services\TaxedSourceInvoiceFactory;
use Modules\VehicleRental\Enums\AgreementAction;
use Modules\VehicleRental\Enums\AgreementAdjustmentCategory;
use Modules\VehicleRental\Enums\AgreementAdjustmentStatus;
use Modules\VehicleRental\Enums\AgreementAdjustmentType;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseChargeSource;
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Enums\BillingFinalizationMode;
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Enums\VehicleUseAction;
use Modules\VehicleRental\Models\AgreementAdjustment;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\BillingCycleChart;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalExpenseAllocation;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\BaseRentBilling;
use Modules\VehicleRental\Services\OwnerSettlementBillingService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RunningChartService;
use Modules\VehicleRental\Services\TwoStageInvoicingService;
use Modules\VehicleRental\Services\VehicleUseService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class RentalBillingDocumentTest extends TestCase
{
    use BuildsRentalFixture;
    use RefreshDatabase;

    public function test_rental_billing_documents_list_and_filter_by_direction(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);

            $ownAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownAg = $agreements->change(AgreementKind::Owner, $context, $ownAg->id, $ownAg->row_version, AgreementAction::Activate);

            $invoiceService = app(InvoiceCreationService::class);

            // 1. Create Customer AR Invoice
            $baseCharge = new CustomerBaseCharge();
            $baseCharge->tenant_id = $context->tenantId;
            $baseCharge->organization_unit_id = $context->organizationUnitId;
            $baseCharge->agreement_id = $custAg->id;
            $baseCharge->period_from = '2026-09-01';
            $baseCharge->period_until = '2026-09-30';
            $baseCharge->amount = '45000.000000';
            $baseCharge->calculation = ['rate' => '45000.000000'];
            $baseCharge->actor_id = $context->actorId;
            $baseCharge->save();

            $invoiceService->create(new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-09-01',
                dueDate: '2026-09-15',
                currencyId: $custAg->currency_id,
                exchangeRate: '1.000000',
                partyType: InvoicePartyType::Customer->value,
                partyId: $custAg->customer_id,
                createdBy: $context->actorId,
                notes: 'Base rental charge for September',
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Monthly Base Rent',
                        quantity: '1.000000',
                        unitPrice: '45000.000000',
                        lineType: InvoiceLineType::Service,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: BaseChargeSource::Customer->value,
                        sourceId: $baseCharge->id,
                        sourceDocumentNumber: $custAg->reference,
                        sourceDocumentDate: '2026-09-01',
                        sourceGrandTotal: '45000.000000',
                        invoicedAmount: '45000.000000',
                    ),
                ],
            ));

            // 2. Create Owner AP Bill via settlement service
            $ownerBaseCharge = new OwnerBaseCharge();
            $ownerBaseCharge->tenant_id = $context->tenantId;
            $ownerBaseCharge->organization_unit_id = $context->organizationUnitId;
            $ownerBaseCharge->agreement_id = $ownAg->id;
            $ownerBaseCharge->period_from = '2026-09-01';
            $ownerBaseCharge->period_until = '2026-09-30';
            $ownerBaseCharge->amount = '30000.000000';
            $ownerBaseCharge->calculation = ['rate' => '30000.000000'];
            $ownerBaseCharge->actor_id = $context->actorId;
            $ownerBaseCharge->save();

            $settlementService = app(OwnerSettlementBillingService::class);
            $settlementService->generateOwnerSettlementBill($context, $ownAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => '10',
            ]);
        });

        // Query Outbound (Customer)
        $responseAr = $this->getJson('/api/v1/vehicle-rental/billing/documents?direction=outbound');
        $responseAr->assertOk();
        $responseAr->assertJsonCount(1, 'data');
        $itemAr = $responseAr->json('data.0');
        $this->assertSame('outbound', $itemAr['direction']);
        $this->assertSame('45000.000000', $itemAr['grand_total']);
        $this->assertNotNull($itemAr['party']);

        // Query Inbound (Owner)
        $responseAp = $this->getJson('/api/v1/vehicle-rental/billing/documents?direction=inbound');
        $responseAp->assertOk();
        $responseAp->assertJsonCount(1, 'data');
        $itemAp = $responseAp->json('data.0');
        $this->assertSame('inbound', $itemAp['direction']);
        $this->assertSame('3000.000000', $itemAp['wht_amount']); // 10% of 30,000
        $this->assertSame('27000.000000', $itemAp['grand_total']); // Net payable after WHT
    }

    public function test_document_search_by_party_and_agreement_reference(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData): void {
            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);

            $invoiceService = app(InvoiceCreationService::class);
            $invoiceService->create(new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-09-01',
                dueDate: '2026-09-15',
                currencyId: $custAg->currency_id,
                exchangeRate: '1.000000',
                partyType: InvoicePartyType::Customer->value,
                partyId: $custAg->customer_id,
                createdBy: $context->actorId,
                notes: 'Special Search Target Note',
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Monthly Charge',
                        quantity: '1.000000',
                        unitPrice: '10000.000000',
                        lineType: InvoiceLineType::Service,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'vehicle_rental_billing_cycle',
                        sourceId: 9999,
                        sourceDocumentNumber: $custAg->reference,
                        sourceDocumentDate: '2026-09-01',
                        sourceGrandTotal: '10000.000000',
                        invoicedAmount: '10000.000000',
                    ),
                ],
            ));
        });

        // Search by agreement reference
        $response = $this->getJson('/api/v1/vehicle-rental/billing/documents?search='.$customerData['reference']);
        $response->assertOk();
        $response->assertJsonCount(1, 'data');

        // Search with non-matching query
        $responseNone = $this->getJson('/api/v1/vehicle-rental/billing/documents?search=NONEXISTENT_XYZ_123');
        $responseNone->assertOk();
        $responseNone->assertJsonCount(0, 'data');
    }

    public function test_kpi_summary_calculates_ar_and_ap_metrics(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData): void {
            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);

            $ownAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownAg = $agreements->change(AgreementKind::Owner, $context, $ownAg->id, $ownAg->row_version, AgreementAction::Activate);

            $invoiceService = app(InvoiceCreationService::class);

            // Create Outbound invoice: 50,000 (overdue)
            $invoiceService->create(new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-08-01',
                dueDate: '2026-08-15',
                currencyId: $custAg->currency_id,
                exchangeRate: '1.000000',
                partyType: InvoicePartyType::Customer->value,
                partyId: $custAg->customer_id,
                createdBy: $context->actorId,
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Rent',
                        quantity: '1.000000',
                        unitPrice: '50000.000000',
                        lineType: InvoiceLineType::Service,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'vehicle_rental_customer_base_charge',
                        sourceId: 1,
                        sourceDocumentNumber: $custAg->reference,
                        sourceDocumentDate: '2026-08-01',
                    ),
                ],
            ));

            // Create Inbound owner bill: 40,000 gross, 4,000 WHT, 36,000 net
            $invoiceService->create(new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Purchase,
                direction: InvoiceDirection::Inbound,
                invoiceDate: '2026-09-01',
                dueDate: '2026-09-30',
                currencyId: $ownAg->currency_id,
                exchangeRate: '1.000000',
                partyType: InvoicePartyType::Supplier->value,
                partyId: $ownAg->supplier_id,
                createdBy: $context->actorId,
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Owner Lease Hire',
                        quantity: '1.000000',
                        unitPrice: '40000.000000',
                        lineType: InvoiceLineType::Service,
                    ),
                ],
                adjustments: [
                    new InvoiceAdjustmentData(
                        name: 'Statutory Withholding Tax Deduction (10%)',
                        adjustmentType: AdjustmentType::Withholding,
                        effect: AdjustmentEffect::Decrease,
                        amount: '4000.000000',
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'vehicle_rental_owner_settlement',
                        sourceId: $ownAg->id,
                        sourceDocumentNumber: $ownAg->reference.'-SETTLE',
                        sourceDocumentDate: '2026-09-01',
                    ),
                ],
            ));
        });

        $response = $this->getJson('/api/v1/vehicle-rental/billing/summary');
        $response->assertOk();

        $data = $response->json('data');
        $this->assertSame('50000.000000', $data['customer_ar']['total_invoiced']);
        $this->assertSame('50000.000000', $data['customer_ar']['total_outstanding']);
        $this->assertSame(1, $data['customer_ar']['overdue_count']);
        $this->assertSame('50000.000000', $data['customer_ar']['overdue_amount']);

        $this->assertSame('40000.000000', $data['owner_ap']['total_gross_billed']);
        $this->assertSame('4000.000000', $data['owner_ap']['total_wht_withheld']);
        $this->assertSame('36000.000000', $data['owner_ap']['total_outstanding']);
    }

    public function test_document_detail_endpoint_and_draft_update(): void
    {
        [$context, $customerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $invoiceId = 0;
        $rowVersion = 1;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$invoiceId, &$rowVersion): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);

            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);

            $invoiceService = app(InvoiceCreationService::class);
            $inv = $invoiceService->create(new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-09-01',
                dueDate: '2026-09-15',
                currencyId: $custAg->currency_id,
                exchangeRate: '1.000000',
                partyType: InvoicePartyType::Customer->value,
                partyId: $custAg->customer_id,
                createdBy: $context->actorId,
                notes: 'Initial note',
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Stage 1 Base Rent',
                        quantity: '1.000000',
                        unitPrice: '25000.000000',
                        lineType: InvoiceLineType::Service,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'vehicle_rental_customer_base_charge',
                        sourceId: 101,
                        sourceDocumentNumber: $custAg->reference,
                        sourceDocumentDate: '2026-09-01',
                    ),
                ],
            ));
            $invoiceId = $inv->id;
            $rowVersion = (int) $inv->row_version;
        });

        // 1. Fetch Detail
        $detailRes = $this->getJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}");
        $detailRes->assertOk();
        $detail = $detailRes->json('data');
        $this->assertSame($invoiceId, $detail['id']);
        $this->assertTrue($detail['can_edit']);
        $this->assertTrue($detail['can_cancel']);
        $this->assertFalse($detail['can_record_payment']); // Draft cannot record payment directly until posted
        $this->assertCount(1, $detail['lines']);
        $this->assertSame('Stage 1 Base Rent', $detail['lines'][0]['description']);
        $this->assertArrayHasKey('discount_amount', $detail['lines'][0]);
        $this->assertArrayHasKey('financial_breakdown', $detail);
        $this->assertArrayHasKey('statement_url', $detail);
        $this->assertSame('25000.000000', $detail['financial_breakdown']['grand_total']);

        // Test statement route
        $statementRes = $this->get("/api/v1/vehicle-rental/billing/documents/{$invoiceId}/statement?format=html");
        $statementRes->assertOk();

        // 2. Update Draft
        $updateRes = $this->putJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}", [
            'expected_version' => $rowVersion,
            'due_date' => '2026-09-25',
            'notes' => 'Updated terms for rental',
        ]);
        $updateRes->assertOk();
        $updated = $updateRes->json('data');
        $this->assertSame('2026-09-25', $updated['due_date']);
        $this->assertSame('Updated terms for rental', $updated['notes']);
    }

    public function test_cancel_draft_document_with_audit_reason(): void
    {
        [$context, $customerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $invoiceId = 0;
        $rowVersion = 1;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$invoiceId, &$rowVersion): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);

            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);

            $invoiceService = app(InvoiceCreationService::class);
            $inv = $invoiceService->create(new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-09-01',
                dueDate: '2026-09-15',
                currencyId: $custAg->currency_id,
                exchangeRate: '1.000000',
                partyType: InvoicePartyType::Customer->value,
                partyId: $custAg->customer_id,
                createdBy: $context->actorId,
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Draft Charge',
                        quantity: '1.000000',
                        unitPrice: '15000.000000',
                        lineType: InvoiceLineType::Service,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'vehicle_rental_customer_base_charge',
                        sourceId: 202,
                        sourceDocumentNumber: $custAg->reference,
                        sourceDocumentDate: '2026-09-01',
                    ),
                ],
            ));
            $invoiceId = $inv->id;
            $rowVersion = (int) $inv->row_version;
        });

        // Attempt cancel with short reason -> fails validation
        $failRes = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}/cancel", [
            'expected_version' => $rowVersion,
            'reason' => 'bad',
        ]);
        $failRes->assertStatus(422);

        // Cancel with proper reason
        $cancelRes = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}/cancel", [
            'expected_version' => $rowVersion,
            'reason' => 'Customer requested rescheduling of booking dates',
        ]);
        $cancelRes->assertOk();
        $cancelled = $cancelRes->json('data');
        $this->assertSame('cancelled', $cancelled['status']);
        $this->assertSame('Customer requested rescheduling of booking dates', $cancelled['cancellation_reason']);
        $this->assertFalse($cancelled['can_cancel']);
        $this->assertFalse($cancelled['can_edit']);
    }

    public function test_posted_document_rejects_direct_cancellation(): void
    {
        [$context, $customerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $invoiceId = 0;
        $rowVersion = 1;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$invoiceId, &$rowVersion): void {
            FinancePostingFixture::seedRentalInvoiceProfiles($context->tenantId, $context->organizationUnitId);

            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);

            $source = new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-09-01',
                dueDate: '2026-09-15',
                currencyId: $custAg->currency_id,
                exchangeRate: '1.000000',
                partyType: InvoicePartyType::Customer->value,
                partyId: $custAg->customer_id,
                createdBy: $context->actorId,
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'To Post Charge',
                        quantity: '1.000000',
                        unitPrice: '12000.000000',
                        lineType: InvoiceLineType::Service,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'vehicle_rental_customer_base_charge',
                        sourceId: 303,
                        sourceDocumentNumber: $custAg->reference,
                        sourceDocumentDate: '2026-09-01',
                    ),
                ],
            );

            $factory = app(TaxedSourceInvoiceFactory::class);
            $prepared = $factory->prepare(
                $source,
                FinancePostingProfileCode::CustomerRentalInvoice,
                FinanceAccountRoleCode::RentalRevenue,
            );

            $invoiceService = app(InvoiceCreationService::class);
            $inv = $invoiceService->create($prepared);

            $statusService = app(InvoiceStatusService::class);
            $inv = $statusService->transition($inv, InvoiceStatus::Approved, $context->actorId);
            $inv = $statusService->transition($inv, InvoiceStatus::Posted, $context->actorId);

            $invoiceId = $inv->id;
            $rowVersion = (int) $inv->row_version;
        });

        // Direct cancel on posted document should fail
        $res = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}/cancel", [
            'expected_version' => $rowVersion,
            'reason' => 'Trying to cancel posted document directly',
        ]);
        $res->assertStatus(422);
    }

    public function test_cancel_draft_document_restores_billing_cycle_and_unblocks_subsequent_cycle(): void
    {
        [$context, $customerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $invoiceId = 0;
        $rowVersion = 1;
        $agreementId = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$invoiceId, &$rowVersion, &$agreementId): void {
            $customerData['terms']['base_rate'] = '60000.000000';
            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);
            $agreementId = $custAg->id;

            // Create Stage 1 Billing Cycle with initial invoice
            $twoStage = app(TwoStageInvoicingService::class);
            $cycle = $twoStage->createCycle(AgreementKind::Customer, $context, $custAg->id, [
                'period_from' => '2026-10-01',
                'period_until' => '2026-10-31',
                'issue_initial_invoice' => true,
                'base_rent_amount' => '60000.000000',
                'billing_mode' => 'without_running_chart',
            ]);

            $this->assertSame(BillingCycleStatus::Stage1Generated, $cycle->status);
            $this->assertNotNull($cycle->initial_invoice_id);

            $inv = Invoice::query()->findOrFail($cycle->initial_invoice_id);
            $invoiceId = $inv->id;
            $rowVersion = (int) $inv->row_version;
        });

        // Cancel the Stage 1 invoice through the dedicated billing cockpit
        $cancelRes = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}/cancel", [
            'expected_version' => $rowVersion,
            'reason' => 'Customer requested date change before cycle finalization',
        ]);
        $cancelRes->assertOk();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $invoiceId, $agreementId): void {
            // Assert invoice is cancelled
            $inv = Invoice::query()->findOrFail($invoiceId);
            $this->assertSame(InvoiceStatus::Cancelled, $inv->status);

            // Assert Billing Cycle was automatically transitioned to Cancelled
            $cycle = BillingCycle::query()->where('initial_invoice_id', $invoiceId)->firstOrFail();
            $this->assertSame(BillingCycleStatus::Cancelled, $cycle->status);

            // Assert creating a new billing cycle for the same period now SUCCEEDS (not blocked by overlap)
            $twoStage = app(TwoStageInvoicingService::class);
            $newCycle = $twoStage->createCycle(AgreementKind::Customer, $context, $agreementId, [
                'period_from' => '2026-10-01',
                'period_until' => '2026-10-31',
                'issue_initial_invoice' => true,
                'base_rent_amount' => '60000.000000',
                'billing_mode' => 'without_running_chart',
            ]);

            $this->assertSame(BillingCycleStatus::Stage1Generated, $newCycle->status);
            $this->assertNotSame($cycle->id, $newCycle->id);
        });
    }

    public function test_cancel_draft_document_voids_underlying_base_charge_and_unblocks_re_billing(): void
    {
        [$context, $customerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
            TenantFeature::PAYMENT,
        ]);

        $invoiceId = 0;
        $rowVersion = 1;
        $chargeId = 0;
        $agreementId = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$invoiceId, &$rowVersion, &$chargeId, &$agreementId): void {
            $customerData['terms']['base_rate'] = '50000.000000';
            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);
            $agreementId = $custAg->id;

            // Create base charge via BaseRentBilling
            $baseBilling = app(BaseRentBilling::class);
            $inv = $baseBilling->create(AgreementKind::Customer, $context, $custAg->id, [
                'from' => '2026-10-01',
                'until' => '2026-10-31',
                'invoice_date' => '2026-10-01',
                'exchange_rate' => '1.000000',
                'policy' => BaseRentPolicy::ActualCalendarDays->value,
                'expected_version' => $custAg->row_version,
            ]);

            $invoiceId = $inv->id;
            $rowVersion = (int) $inv->row_version;
            $source = $inv->sources()->where('source_type', BaseChargeSource::Customer->value)->firstOrFail();
            $chargeId = (int) $source->source_id;
        });

        // Cancel the draft invoice
        $cancelRes = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}/cancel", [
            'expected_version' => $rowVersion,
            'reason' => 'Contract modified for October',
        ]);
        $cancelRes->assertOk();

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $chargeId, $agreementId): void {
            // Assert charge is now voided
            $charge = CustomerBaseCharge::query()->findOrFail($chargeId);
            $this->assertNotNull($charge->voided_at);
            $this->assertSame('Contract modified for October', $charge->void_reason);

            // Assert re-billing same period now SUCCEEDS because old charge is voided
            $custAg = CustomerAgreement::query()->findOrFail($agreementId);
            $baseBilling = app(BaseRentBilling::class);
            $newInv = $baseBilling->create(AgreementKind::Customer, $context, $custAg->id, [
                'from' => '2026-10-01',
                'until' => '2026-10-31',
                'invoice_date' => '2026-10-01',
                'exchange_rate' => '1.000000',
                'policy' => BaseRentPolicy::ActualCalendarDays->value,
                'expected_version' => $custAg->row_version,
            ]);

            $this->assertNotSame($chargeId, (int) $newInv->sources()->where('source_type', BaseChargeSource::Customer->value)->value('source_id'));
        });
    }

    public function test_view_only_user_cannot_cancel_or_update_draft_document(): void
    {
        // Login as user with only view permissions (no customer-bill or owner-bill)
        [$context, $customerData] = $this->loginFixture([
            RentalAuthorization::CUSTOMER_VIEW,
            RentalAuthorization::OWNER_VIEW,
        ], [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
        ]);

        $invoiceId = 0;
        $rowVersion = 1;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$invoiceId, &$rowVersion): void {
            $invoiceService = app(InvoiceCreationService::class);
            $inv = $invoiceService->create(new CreateInvoiceData(
                tenantId: $context->tenantId,
                organizationUnitId: $context->organizationUnitId,
                invoiceType: InvoiceType::Sales,
                direction: InvoiceDirection::Outbound,
                invoiceDate: '2026-09-01',
                dueDate: '2026-09-15',
                currencyId: $customerData['currency_id'],
                exchangeRate: '1.000000',
                partyType: InvoicePartyType::Customer->value,
                partyId: $customerData['party_id'],
                createdBy: $context->actorId,
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'View only test charge',
                        quantity: '1.000000',
                        unitPrice: '10000.000000',
                        lineType: InvoiceLineType::Service,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $context->tenantId,
                        organizationUnitId: $context->organizationUnitId,
                        sourceType: 'vehicle_rental_customer_base_charge',
                        sourceId: 505,
                        sourceDocumentNumber: 'VRC-TEST-REF',
                        sourceDocumentDate: '2026-09-01',
                    ),
                ],
            ));

            $invoiceId = $inv->id;
            $rowVersion = (int) $inv->row_version;
        });

        // Detail endpoint shows can_cancel = false and can_edit = false for view-only user
        $detailRes = $this->getJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}");
        $detailRes->assertOk();
        $this->assertFalse($detailRes->json('data.can_cancel'));
        $this->assertFalse($detailRes->json('data.can_edit'));

        // Attempt to cancel fails with 403 Forbidden
        $cancelRes = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}/cancel", [
            'expected_version' => $rowVersion,
            'reason' => 'Unauthorized attempt to cancel',
        ]);
        $cancelRes->assertStatus(403);

        // Attempt to update draft fails with 403 Forbidden
        $updateRes = $this->putJson("/api/v1/vehicle-rental/billing/documents/{$invoiceId}", [
            'expected_version' => $rowVersion,
            'notes' => 'Unauthorized update',
        ]);
        $updateRes->assertStatus(403);
    }

    public function test_multi_tenant_isolation_rejects_cross_tenant_billing_access(): void
    {
        // Tenant A setup
        [$contextA, $customerDataA] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
        ]);

        $invoiceIdA = 0;
        $this->withTenantExecutionContext($contextA->tenantId, function () use ($contextA, $customerDataA, &$invoiceIdA): void {
            $customerDataA['terms']['base_rate'] = '40000.000000';
            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $contextA, $customerDataA);
            $custAg = $agreements->change(AgreementKind::Customer, $contextA, $custAg->id, $custAg->row_version, AgreementAction::Activate);

            $baseBilling = app(BaseRentBilling::class);
            $inv = $baseBilling->create(AgreementKind::Customer, $contextA, $custAg->id, [
                'from' => '2026-11-01',
                'until' => '2026-11-30',
                'invoice_date' => '2026-11-01',
                'exchange_rate' => '1.000000',
                'policy' => BaseRentPolicy::ActualCalendarDays->value,
                'expected_version' => $custAg->row_version,
            ]);
            $invoiceIdA = $inv->id;
        });

        // Tenant B login
        [$contextB] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
        ]);

        // Tenant B lists documents -> empty list (Tenant A invoice not visible)
        $listRes = $this->getJson('/api/v1/vehicle-rental/billing/documents?direction=outbound');
        $listRes->assertOk();
        $this->assertCount(0, $listRes->json('data'));

        // Tenant B detail on Tenant A invoice -> 404
        $detailRes = $this->getJson("/api/v1/vehicle-rental/billing/documents/{$invoiceIdA}");
        $detailRes->assertNotFound();

        // Tenant B update on Tenant A invoice -> 404
        $updateRes = $this->putJson("/api/v1/vehicle-rental/billing/documents/{$invoiceIdA}", [
            'expected_version' => 1,
            'notes' => 'Cross-tenant attack',
        ]);
        $updateRes->assertNotFound();

        // Tenant B cancel on Tenant A invoice -> 404
        $cancelRes = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$invoiceIdA}/cancel", [
            'expected_version' => 1,
            'reason' => 'Cross-tenant attack',
        ]);
        $cancelRes->assertNotFound();
    }

    public function test_cancel_draft_supplementary_invoice_reverts_cycle_to_stage2_attached_without_destroying_cycle(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
        ]);

        $suppInvoiceId = 0;
        $rowVersion = 1;
        $cycleId = 0;
        $initialInvId = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$suppInvoiceId, &$rowVersion, &$cycleId, &$initialInvId): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms']['base_rate'] = '30000.000000';
            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $custAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
            ]);

            $this->assertNotNull($cycle->initial_invoice_id);
            $cycleId = $cycle->id;
            $initialInvId = $cycle->initial_invoice_id;

            // Add ad-hoc charge of 5,000 so supplementary invoice will be issued
            $invoicing->addItem($context, $cycle->id, [
                'item_type' => 'addition',
                'category' => 'other',
                'description' => 'Airport toll and parking surcharge',
                'quantity' => '1.000000',
                'unit_rate' => '5000.000000',
            ], (int) $cycle->row_version);
            $cycle->refresh();

            // Finalize in Mode A -> issues supplementary invoice
            $cycle = $invoicing->finalize($context, $cycle->id, [
                'finalization_mode' => 'supplementary_invoice',
            ], (int) $cycle->row_version);
            $this->assertNotNull($cycle->supplementary_invoice_id);
            $suppInvoiceId = $cycle->supplementary_invoice_id;

            $suppInvoice = Invoice::findOrFail($suppInvoiceId);
            $this->assertSame(InvoiceStatus::Draft, $suppInvoice->status);
            $rowVersion = (int) $suppInvoice->row_version;
        });

        // Cancel supplementary invoice outside withTenantExecutionContext
        $cancelRes = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$suppInvoiceId}/cancel", [
            'expected_version' => $rowVersion,
            'reason' => 'Disputed airport surcharge',
        ]);
        $cancelRes->assertOk();

        // Refresh cycle: supplementary_invoice_id must be null, status must be Stage2Attached, NOT Cancelled!
        // Audit and total billed fields must be properly reset, and Stage 2 must be re-finalizable!
        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $cycleId, $initialInvId): void {
            $cycle = BillingCycle::findOrFail($cycleId);
            $this->assertNull($cycle->supplementary_invoice_id);
            $this->assertSame(BillingCycleStatus::Stage2Attached, $cycle->status);
            $this->assertSame($initialInvId, $cycle->initial_invoice_id);
            $this->assertNull($cycle->finalized_at);
            $this->assertNull($cycle->finalized_by);
            $this->assertSame(BillingFinalizationMode::SupplementaryInvoice, $cycle->finalization_mode);

            $initialInv = Invoice::findOrFail($initialInvId);
            $this->assertSame((string) $initialInv->grand_total, (string) $cycle->total_billed_amount);

            // Verify Stage 2 can now be successfully finalized again
            $invoicing = app(TwoStageInvoicingService::class);
            $refinalized = $invoicing->finalize($context, $cycle->id, [
                'finalization_mode' => 'supplementary_invoice',
            ], (int) $cycle->row_version);

            $this->assertSame(BillingCycleStatus::Finalized, $refinalized->status);
            $this->assertNotNull($refinalized->supplementary_invoice_id);
            $this->assertNotSame($initialInvId, $refinalized->supplementary_invoice_id);
        });
    }

    public function test_cancel_draft_owner_settlement_bill_restores_owner_cycle_and_agreement_adjustments(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
        ]);

        $billId = 0;
        $rowVersion = 1;
        $cycleId = 0;
        $adjId = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData, &$billId, &$rowVersion, &$cycleId, &$adjId): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '40000.000000'];
            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            // Create unbilled owner billing cycle
            $twoStage = app(TwoStageInvoicingService::class);
            $cycle = $twoStage->createCycle(AgreementKind::Owner, $context, $ownerAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => false,
                'base_rent_amount' => '40000.000000',
                'billing_mode' => 'without_running_chart',
            ]);
            $cycleId = $cycle->id;

            // Create authorized AgreementAdjustment
            $adj = new AgreementAdjustment();
            $adj->tenant_id = $context->tenantId;
            $adj->organization_unit_id = $context->organizationUnitId;
            $adj->owner_agreement_id = $ownerAg->id;
            $adj->adjustment_type = AgreementAdjustmentType::DebitNote;
            $adj->category = AgreementAdjustmentCategory::Other;
            $adj->status = AgreementAdjustmentStatus::Authorized;
            $adj->amount = '3000.000000';
            $adj->original_amount = '3000.000000';
            $adj->tax_amount = '0.000000';
            $adj->reason = 'Fuel incentive addition';
            $adj->actor_id = $context->actorId;
            $adj->authorized_by_user_id = $context->actorId;
            $adj->authorized_at = now();
            $adj->row_version = 1;
            $adj->save();
            $adjId = $adj->id;

            // Generate owner settlement bill
            $settleService = app(OwnerSettlementBillingService::class);
            $bill = $settleService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => '10.000000',
                'invoice_date' => '2026-09-30',
            ]);
            $billId = $bill->id;
            $rowVersion = (int) $bill->row_version;

            $cycle->refresh();
            $this->assertSame(BillingCycleStatus::Finalized, $cycle->status);
            $this->assertSame($bill->id, $cycle->final_invoice_id);

            $adj->refresh();
            $this->assertSame(AgreementAdjustmentStatus::Applied, $adj->status);
            $this->assertSame($bill->id, $adj->invoice_id);
        });

        // Cancel the draft settlement bill
        $cancelRes = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$billId}/cancel", [
            'expected_version' => $rowVersion,
            'reason' => 'Accounting adjustment needed',
        ]);
        $cancelRes->assertOk();

        // Verify owner cycle is restored (NOT cancelled) and adjustment is restored to Authorized
        $this->withTenantExecutionContext($context->tenantId, function () use ($cycleId, $adjId): void {
            $cycle = BillingCycle::findOrFail($cycleId);
            $this->assertNull($cycle->final_invoice_id);
            $this->assertNotSame(BillingCycleStatus::Cancelled, $cycle->status);
            $this->assertNull($cycle->finalized_at);

            $adj = AgreementAdjustment::findOrFail($adjId);
            $this->assertNull($adj->invoice_id);
            $this->assertSame(AgreementAdjustmentStatus::Authorized, $adj->status);
        });
    }

    public function test_cancel_document_resets_rental_expense_allocation_status_to_confirmed(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
        ]);

        $billId = 0;
        $rowVersion = 1;
        $allocId = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData, &$billId, &$rowVersion, &$allocId): void {
            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['ends_on'] = '2026-09-30';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerData['terms'] = ['base_rate' => '50000.000000'];
            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $baseCharge = new OwnerBaseCharge();
            $baseCharge->tenant_id = $context->tenantId;
            $baseCharge->organization_unit_id = $context->organizationUnitId;
            $baseCharge->agreement_id = $ownerAg->id;
            $baseCharge->period_from = '2026-09-01';
            $baseCharge->period_until = '2026-09-30';
            $baseCharge->amount = '50000.000000';
            $baseCharge->calculation = ['rate' => '50000.000000'];
            $baseCharge->actor_id = $context->actorId;
            $baseCharge->save();

            $expense = new RentalExpense();
            $expense->tenant_id = $context->tenantId;
            $expense->organization_unit_id = $context->organizationUnitId;
            $expense->expense_number = 'EXP-CANCEL-001';
            $expense->expense_type = RentalExpenseType::Maintenance;
            $expense->status = RentalExpenseStatus::Approved;
            $expense->vehicle_id = $ownerData['vehicle_id'];
            $expense->vehicle_label_snapshot = 'CAR-01';
            $expense->fingerprint = 'FP-CANCEL-001';
            $expense->expense_date = '2026-09-10';
            $expense->currency_id = $ownerAg->currency_id;
            $expense->currency_code_snapshot = 'USD';
            $expense->gross_amount = '5000.000000';
            $expense->tax_amount = '0.000000';
            $expense->net_amount = '5000.000000';
            $expense->description = 'Tire replacement';
            $expense->created_by = $context->actorId;
            $expense->save();

            $alloc = new RentalExpenseAllocation();
            $alloc->tenant_id = $context->tenantId;
            $alloc->organization_unit_id = $context->organizationUnitId;
            $alloc->expense_id = $expense->id;
            $alloc->sequence = 1;
            $alloc->allocation_type = RentalExpenseAllocationType::OwnerDeduction;
            $alloc->target_owner_agreement_id = $ownerAg->id;
            $alloc->net_amount = '5000.000000';
            $alloc->total_amount = '5000.000000';
            $alloc->status = RentalExpenseAllocationStatus::Confirmed;
            $alloc->save();

            $allocId = $alloc->id;

            $settlementService = app(OwnerSettlementBillingService::class);
            $bill = $settlementService->generateOwnerSettlementBill($context, $ownerAg->id, [
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'wht_rate' => '0.000000',
                'invoice_date' => '2026-09-30',
            ]);

            $billId = $bill->id;
            $rowVersion = (int) $bill->row_version;
            $alloc->refresh();
            $this->assertSame($bill->id, $alloc->invoice_id);
            $this->assertSame(RentalExpenseAllocationStatus::Invoiced, $alloc->status);
        });

        // Cancel draft bill outside withTenantExecutionContext
        $cancelRes = $this->postJson("/api/v1/vehicle-rental/billing/documents/{$billId}/cancel", [
            'expected_version' => $rowVersion,
            'reason' => 'Billing dispute',
        ]);
        $cancelRes->assertOk();

        // Allocation must be unlinked AND status must be reset to Confirmed
        $this->withTenantExecutionContext($context->tenantId, function () use ($allocId): void {
            $alloc = RentalExpenseAllocation::findOrFail($allocId);
            $this->assertNull($alloc->invoice_id);
            $this->assertSame(RentalExpenseAllocationStatus::Confirmed, $alloc->status);
        });
    }

    public function test_document_register_resolves_driver_from_cycle_charts_when_items_driver_empty(): void
    {
        [$context, $customerData, $ownerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
        ]);

        $initialInvId = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, $ownerData, &$initialInvId): void {
            $customerData['starts_on'] = '2026-09-01';
            $customerData['agreed_on'] = '2026-09-01';
            $customerData['terms']['base_rate'] = '20000.000000';
            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, $customerData);
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);

            $ownerData['starts_on'] = '2026-09-01';
            $ownerData['agreed_on'] = '2026-09-01';
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, $ownerData);
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);

            $uses = app(VehicleUseService::class);
            $use = $uses->plan($context, $custAg->id, $custAg->row_version, [
                'vehicle_id' => $ownerData['vehicle_id'],
                'owner_agreement_id' => $ownerAg->id,
                'starts_at' => '2026-09-01T08:00:00+00:00',
                'ends_at' => '2026-09-30T18:00:00+00:00',
            ]);
            $use = $uses->transition($context, $use->id, $use->row_version, VehicleUseAction::Handover, [
                'occurred_at' => '2026-09-01T08:00:00+00:00',
                'odometer' => '10000.000000',
                'reason' => 'Customer handover',
            ]);

            $charts = app(RunningChartService::class);
            $chart = $charts->create($context, $use->id, (int) $use->row_version, [
                'reference' => 'RC-DRIVER-001',
                'starts_at' => '2026-09-08T08:00:00+00:00',
                'ends_at' => '2026-09-08T20:00:00+00:00',
                'start_odometer' => '10000.000000',
                'end_odometer' => '10100.000000',
                'commercial_km' => '100.000000',
                'driver_observation' => 'Sunil Perera',
            ]);
            $chart = $charts->change($context, $chart->id, $chart->row_version, RunningChartAction::Finalize, ['reason' => 'Trip concluded']);

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $custAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => true,
            ]);

            $cycle = $invoicing->attachRunningCharts($context, $cycle->id, [$chart->id], (int) $cycle->row_version);
            $initialInvId = $cycle->initial_invoice_id;
        });

        $res = $this->getJson('/api/v1/vehicle-rental/billing/documents?direction=outbound');
        $res->assertOk();

        $doc = collect($res->json('data'))->firstWhere('id', $initialInvId);
        $this->assertNotNull($doc);
        $this->assertSame('Sunil Perera', $doc['driver_name']);
    }

    public function test_quotation_preview_endpoint_returns_itemized_preview_without_creating_database_records(): void
    {
        [$context, $customerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
        ]);

        $cycleId = null;
        $custAgId = null;
        $initialInvoiceCount = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $customerData, &$cycleId, &$custAgId, &$initialInvoiceCount): void {
            $initialInvoiceCount = Invoice::query()->where('tenant_id', $context->tenantId)->count();

            $customerData['starts_on'] = '2026-09-01';
            $agreements = app(AgreementService::class);
            $custAg = $agreements->create(AgreementKind::Customer, $context, array_merge($customerData, [
                'terms' => [
                    'base_rate' => '60000.000000',
                    'included_km' => '50.000000',
                    'excess_km_rate' => '50.000000',
                    'normal_ot_rate' => '400.000000',
                    'double_ot_rate' => '600.000000',
                    'triple_ot_rate' => '800.000000',
                    'night_out_rate' => '1500.000000',
                ],
            ]));
            $custAg = $agreements->change(AgreementKind::Customer, $context, $custAg->id, $custAg->row_version, AgreementAction::Activate);
            $custAgId = $custAg->id;

            $invoicing = app(TwoStageInvoicingService::class);
            $cycle = $invoicing->createCycle(AgreementKind::Customer, $context, $custAg->id, [
                'period_from' => '2026-09-01',
                'period_until' => '2026-09-30',
                'issue_initial_invoice' => false,
            ]);
            $cycleId = $cycle->id;
        });

        // Hit quotation preview endpoint
        $res = $this->postJson('/api/v1/vehicle-rental/billing/quotation-preview', [
            'billing_cycle_id' => $cycleId,
        ]);

        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                'quotation_number',
                'quotation_date',
                'direction',
                'customer',
                'agreement',
                'billing_cycle',
                'period',
                'lines',
                'telematics',
                'financial_breakdown' => [
                    'base_rent',
                    'excess_km',
                    'overtime',
                    'night_out',
                    'tolls',
                    'additions',
                    'deductions',
                    'gross_subtotal',
                    'net_subtotal',
                    'tax_amount',
                    'grand_total',
                ],
            ],
        ]);

        // Verify zero invoices were created
        $finalInvoiceCount = Invoice::query()->where('tenant_id', $context->tenantId)->count();
        $this->assertSame($initialInvoiceCount, $finalInvoiceCount);

        // Also test direct preview by customer agreement id without billing cycle id
        $resDirect = $this->postJson('/api/v1/vehicle-rental/billing/quotation-preview', [
            'customer_agreement_id' => $custAgId,
            'period_from' => '2026-09-01',
            'period_until' => '2026-09-30',
        ]);
        $resDirect->assertOk();
        $this->assertNotEmpty($resDirect->json('data.lines'));
        $this->assertSame($initialInvoiceCount, Invoice::query()->where('tenant_id', $context->tenantId)->count());
    }

    public function test_owner_bill_preview_endpoint_returns_itemized_breakdown_without_creating_database_records(): void
    {
        [$context, , $ownerData] = $this->loginFixture(null, [
            TenantFeature::VEHICLE_RENTAL,
            TenantFeature::INVOICE,
        ]);

        $ownerAgId = null;
        $initialInvoiceCount = 0;

        $this->withTenantExecutionContext($context->tenantId, function () use ($context, $ownerData, &$ownerAgId, &$initialInvoiceCount): void {
            $initialInvoiceCount = Invoice::query()->where('tenant_id', $context->tenantId)->count();

            $agreements = app(AgreementService::class);
            $ownerAg = $agreements->create(AgreementKind::Owner, $context, array_merge($ownerData, [
                'terms' => [
                    'base_rate' => '50000.000000',
                ],
            ]));
            $ownerAg = $agreements->change(AgreementKind::Owner, $context, $ownerAg->id, $ownerAg->row_version, AgreementAction::Activate);
            $ownerAgId = $ownerAg->id;
        });

        // Hit owner bill preview endpoint
        $res = $this->postJson('/api/v1/vehicle-rental/billing/owner-bill-preview', [
            'owner_agreement_id' => $ownerAgId,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'wht_rate' => '10.0',
        ]);

        $res->assertOk();
        $res->assertJsonStructure([
            'data' => [
                'bill_number',
                'direction',
                'document_date',
                'supplier',
                'agreement',
                'period',
                'wht_rate',
                'lines',
                'adjustments',
                'financial_breakdown' => [
                    'gross_payable',
                    'base_payable',
                    'wht_amount',
                    'total_deductions',
                    'net_payable',
                ],
            ],
        ]);

        // Verify net payable accounts for 10% WHT (50000 gross - 5000 WHT = 45000 net)
        $data = $res->json('data.financial_breakdown');
        $this->assertSame('50000.000000', $data['gross_payable']);
        $this->assertSame('5000.000000', $data['wht_amount']);
        $this->assertSame('45000.000000', $data['net_payable']);

        // Verify zero invoices were created
        $finalInvoiceCount = Invoice::query()->where('tenant_id', $context->tenantId)->count();
        $this->assertSame($initialInvoiceCount, $finalInvoiceCount);
    }
}
