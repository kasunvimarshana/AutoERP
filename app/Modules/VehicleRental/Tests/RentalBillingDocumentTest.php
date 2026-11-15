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
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\BaseChargeSource;
use Modules\VehicleRental\Enums\BaseRentPolicy;
use Modules\VehicleRental\Enums\BillingCycleStatus;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Services\AgreementService;
use Modules\VehicleRental\Services\BaseRentBilling;
use Modules\VehicleRental\Services\OwnerSettlementBillingService;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\TwoStageInvoicingService;
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
}
