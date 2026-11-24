<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Tests\Feature\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;
use Modules\Expense\Enums\ExpenseStatus;
use Modules\Expense\Models\Expense;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Finance\Services\AgingReportService;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\InvoiceDirection as InvoiceDocDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Invoice\Services\InvoicePostingPlanFactory;
use Modules\Invoice\Services\InvoiceReversalService;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Invoice\Services\Tax\InvoiceTaxDocumentMapper;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentType as PaymentDocType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Services\PaymentCashReceiptDatePolicy;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Services\GoodsReceiptNoteService;
use Modules\Purchase\Services\PurchaseGoodsReceiptPostingCoordinator;
use Modules\Purchase\Services\Tax\GoodsReceiptNoteTaxDocumentMapper;
use Tests\Support\FinancePostingFixture;
use Tests\Support\OrganizationUnitFixture;
use Tests\TestCase;

final class BackdatedCrudSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_and_grn_tax_mappers_prioritize_business_dates(): void
    {
        $tenantId = $this->createTenant();

        $invoice = (new Invoice)->forceFill([
            'id' => 101,
            'tenant_id' => $tenantId,
            'invoice_number' => 'INV-TAX-001',
            'invoice_date' => '2026-05-01',
            'posted_at' => '2026-11-24 10:00:00',
            'direction' => InvoiceDocDirection::Outbound,
            'invoice_type' => InvoiceType::Manual,
            'party_type' => 'customer',
            'party_id' => 1,
        ]);
        $invoice->setRelation('lines', collect());

        $invoiceMapper = app(InvoiceTaxDocumentMapper::class);
        $taxDoc = $invoiceMapper->map($invoice);
        $this->assertSame('2026-05-01', $taxDoc->transactionDate);
        $this->assertSame('2026-05-01', $taxDoc->sourceDate);

        // When explicit transaction date is passed, it should be honored
        $taxDocExplicit = $invoiceMapper->map($invoice, '2026-05-15');
        $this->assertSame('2026-05-15', $taxDocExplicit->transactionDate);

        // Goods receipt note tax mapper
        $grn = (new GoodsReceiptNote)->forceFill([
            'id' => 201,
            'tenant_id' => $tenantId,
            'grn_number' => 'GRN-TAX-001',
            'received_date' => '2026-05-01',
            'posted_at' => '2026-11-24 10:00:00',
            'supplier_id' => 5,
        ]);
        $grn->setRelation('lines', collect());

        $grnMapper = app(GoodsReceiptNoteTaxDocumentMapper::class);
        $grnTaxDoc = $grnMapper->map($grn);
        $this->assertSame('2026-05-01', $grnTaxDoc->transactionDate);
        $this->assertSame('2026-05-01', $grnTaxDoc->sourceDate);
    }

    public function test_backdated_invoice_can_be_reversed_before_posted_at_timestamp(): void
    {
        $tenantId = $this->createTenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);
            $creator = app(InvoiceCreationService::class);
            $statusService = app(InvoiceStatusService::class);
            $reversalService = app(InvoiceReversalService::class);
            $postingPlanFactory = app(InvoicePostingPlanFactory::class);

            $postingPlan = $postingPlanFactory->outbound(
                FinancePostingProfileCode::SalesInvoice,
                '2026-05-01',
                FinanceAccountRoleCode::Revenue,
                '100.000000',
                description: 'Test service',
            );

            $invoice = $creator->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Manual,
                direction: InvoiceDocDirection::Outbound,
                invoiceDate: '2026-05-01',
                invoiceNumber: 'INV-BACKDATED-001',
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Test service',
                        quantity: '1.000000',
                        unitPrice: '100.000000',
                        sourceLineType: 'manual_line',
                        sourceLineId: 1,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $tenantId,
                        sourceType: 'manual',
                        sourceId: 1,
                        sourceSubtotal: '100.000000',
                        sourceAdjustmentTotal: '0.000000',
                        sourceGrandTotal: '100.000000',
                    ),
                ],
                sourceLines: [
                    new InvoiceSourceLineData(
                        tenantId: $tenantId,
                        sourceType: 'manual',
                        sourceId: 1,
                        sourceLineType: 'manual_line',
                        sourceLineId: 1,
                        sourceQuantity: '1.000000',
                        invoicedQuantity: '1.000000',
                        sourceUnitPrice: '100.000000',
                        sourceLineTotal: '100.000000',
                    ),
                ],
                postingPlan: $postingPlan,
            ));

            $invoice = $statusService->transitionIfVersion($invoice, InvoiceStatus::Approved, $invoice->row_version);
            $invoice = $statusService->transitionIfVersion($invoice, InvoiceStatus::Posted, $invoice->row_version);

            // Invoice posted_at is now() (today), but invoice_date is 2026-05-01
            // Reversing on 2026-05-10 must succeed (prior to fix it was blocked because 2026-05-10 < posted_at)
            $reversed = $reversalService->reverse(
                $invoice,
                $invoice->row_version,
                '2026-05-10',
                'Correction of backdated bill',
            );

            $this->assertSame(InvoiceStatus::Reversed, $reversed->status);

            // Reversal date before invoice date must still be rejected
            $postingPlan2 = $postingPlanFactory->outbound(
                FinancePostingProfileCode::SalesInvoice,
                '2026-05-01',
                FinanceAccountRoleCode::Revenue,
                '50.000000',
                description: 'Test service 2',
            );

            $another = $creator->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Manual,
                direction: InvoiceDocDirection::Outbound,
                invoiceDate: '2026-05-01',
                invoiceNumber: 'INV-BACKDATED-002',
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Test service 2',
                        quantity: '1.000000',
                        unitPrice: '50.000000',
                        sourceLineType: 'manual_line',
                        sourceLineId: 2,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $tenantId,
                        sourceType: 'manual',
                        sourceId: 2,
                        sourceSubtotal: '50.000000',
                        sourceAdjustmentTotal: '0.000000',
                        sourceGrandTotal: '50.000000',
                    ),
                ],
                sourceLines: [
                    new InvoiceSourceLineData(
                        tenantId: $tenantId,
                        sourceType: 'manual',
                        sourceId: 2,
                        sourceLineType: 'manual_line',
                        sourceLineId: 2,
                        sourceQuantity: '1.000000',
                        invoicedQuantity: '1.000000',
                        sourceUnitPrice: '50.000000',
                        sourceLineTotal: '50.000000',
                    ),
                ],
                postingPlan: $postingPlan2,
            ));
            $another = $statusService->transitionIfVersion($another, InvoiceStatus::Approved, $another->row_version);
            $another = $statusService->transitionIfVersion($another, InvoiceStatus::Posted, $another->row_version);

            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('Invoice reversal date cannot be before the invoice date.');
            $reversalService->reverse(
                $another,
                $another->row_version,
                '2026-04-30',
                'Invalid early reversal',
            );
        });
    }

    public function test_fifo_valuation_layers_order_by_layer_date_for_backdated_receipts(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId);
        $item = $this->createItem($tenantId, 'FIFO-BACKDATE', costing: CostingMethod::Fifo);

        $movements = app(StockMovementService::class);

        // 1. First physical insert is a receipt dated 2026-06-10 with unit cost 10.00
        $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-10',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '10.000000',
            unitCost: '10.000000',
        )));

        // 2. Second physical insert is backdated to 2026-06-01 with unit cost 4.00
        $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-01',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '5.000000',
            unitCost: '4.000000',
        )));

        // Verify layer dates are persisted
        $layers = $this->withTenantExecutionContext($tenantId, fn () => InventoryValuationLayer::query()
            ->where('tenant_id', $tenantId)
            ->where('item_id', $item->getKey())
            ->orderBy('id')
            ->get());
        $this->assertCount(2, $layers);
        $this->assertSame('2026-06-10', $layers[0]->layer_date?->toDateString());
        $this->assertSame('2026-06-01', $layers[1]->layer_date?->toDateString());

        // 3. Issue 2 units on 2026-06-15.
        // FIFO must consume the backdated receipt (from 2026-06-01 @ 4.00), NOT the first created receipt (10.00)!
        $issue = $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-15',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '2.000000',
        )));

        $this->assertSame('4.000000', (string) $issue->unit_cost);
        $this->assertSame('8.000000', (string) $issue->total_cost);
    }

    public function test_backdated_inventory_issue_projects_running_balances_and_prevents_subsequent_negative_stock(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId);
        $item = $this->createItem($tenantId, 'STOCK-PROJ');

        $movements = app(StockMovementService::class);

        // 1. Receipt of 10 on 2026-06-01 (balance = 10)
        $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-01',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '10.000000',
            unitCost: '2.000000',
        )));

        // 2. Issue of 8 on 2026-06-10 (balance becomes 2)
        $issueLater = $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-10',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '8.000000',
        )));
        $this->assertSame('2.000000', (string) $issueLater->balance_quantity_after);

        // 3. Attempt to backdate an issue of 5 on 2026-06-05.
        // On 2026-06-05, stock was 10, so 10 - 5 = 5 >= 0.
        // BUT on 2026-06-10, 8 was issued! If 5 was issued on June 5, then on June 10 balance would be -3!
        // Must throw InvalidArgumentException preventing negative stock!
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory issue would cause negative stock in subsequent periods.');

        $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-05',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '5.000000',
        )));
    }

    public function test_backdated_inventory_issue_updates_subsequent_running_balances_when_valid(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId);
        $item = $this->createItem($tenantId, 'STOCK-PROJ-VALID');

        $movements = app(StockMovementService::class);

        // Receipt of 10 on 2026-06-01
        $receipt = $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-01',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '10.000000',
            unitCost: '2.000000',
        )));
        $this->assertSame('10.000000', (string) $receipt->balance_quantity_after);

        // Issue of 8 on 2026-06-10 (balance = 2)
        $subsequentIssue = $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-10',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '8.000000',
        )));
        $this->assertSame('2.000000', (string) $subsequentIssue->balance_quantity_after);

        // Backdate an issue of 1 on 2026-06-05 (safe: minimum subsequent balance is 2 - 1 = 1 >= 0)
        $backdatedIssue = $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-05',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '1.000000',
        )));

        // The backdated movement's balance_quantity_after should be projected as of June 5: 10 - 1 = 9
        $this->assertSame('9.000000', (string) $backdatedIssue->balance_quantity_after);

        // The subsequent movement on June 10 must have its balance_quantity_after adjusted from 2 to 1
        $subsequentIssue->refresh();
        $this->assertSame('1.000000', (string) $subsequentIssue->balance_quantity_after);

        // Live balance must be 1
        $availability = $this->withTenantExecutionContext($tenantId, fn () => app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId)));
        $this->assertSame('1.000000', $availability->quantityOnHand);
    }

    public function test_backdated_movement_updates_subsequent_reversed_movements_without_logic_exception(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId);
        $item = $this->createItem($tenantId, 'REV-PROJ-TEST');
        $movements = app(StockMovementService::class);

        // 1. Receipt of 10 on 2026-06-01
        $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-01',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '10.000000',
            unitCost: '5.000000',
        )));

        // 2. Receipt of 5 on 2026-06-10
        $m2 = $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-10',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '5.000000',
            unitCost: '5.000000',
        )));

        // 3. Reverse m2 on 2026-06-11
        $this->withTenantExecutionContext($tenantId, fn () => $movements->reverse($m2));
        $m2->refresh();
        $this->assertSame('reversed', $m2->status->value);

        // 4. Backdate a receipt of 2 on 2026-06-05 (which is before m2 and its reversal!)
        $backdated = $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-05',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '2.000000',
            unitCost: '5.000000',
        )));

        $this->assertSame('12.000000', (string) $backdated->balance_quantity_after);
        $m2->refresh();
        $this->assertSame('17.000000', (string) $m2->balance_quantity_after);
    }

    public function test_future_dated_payments_are_rejected_for_outbound_and_non_cash(): void
    {
        $tenantId = $this->createTenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $policy = app(PaymentCashReceiptDatePolicy::class);

            // 1. Inbound non-cash payment in the future
            $paymentBank = (new Payment)->forceFill([
                'tenant_id' => $tenantId,
                'direction' => PaymentDirection::Inbound,
                'payment_type' => PaymentDocType::CustomerReceipt,
                'payment_date' => now()->addDays(5)->toDateString(),
            ]);
            $paymentBank->setRelation('lines', collect([(new PaymentLine)->forceFill(['payment_method_type_snapshot' => 'bank_transfer'])]));

            try {
                $policy->assertCanPost($paymentBank, 1);
                $this->fail('Expected future non-cash payment to be rejected.');
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Payment date cannot be in the future.', $e->getMessage());
            }

            // 2. Outbound supplier payment in the future
            $paymentOutbound = (new Payment)->forceFill([
                'tenant_id' => $tenantId,
                'direction' => PaymentDirection::Outbound,
                'payment_type' => PaymentDocType::SupplierPayment,
                'payment_date' => now()->addDays(3)->toDateString(),
            ]);
            $paymentOutbound->setRelation('lines', collect([(new PaymentLine)->forceFill(['payment_method_type_snapshot' => 'cash'])]));

            try {
                $policy->assertCanPost($paymentOutbound, 1);
                $this->fail('Expected future outbound payment to be rejected.');
            } catch (InvalidArgumentException $e) {
                $this->assertSame('Payment date cannot be in the future.', $e->getMessage());
            }
        });
    }

    public function test_stock_adjustment_validates_historical_stock_as_of_adjustment_date(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId);
        $item = $this->createItem($tenantId, 'ADJ-HIST');

        $movements = app(StockMovementService::class);
        $adjustments = app(StockAdjustmentService::class);

        // Receipt of 10 on 2026-06-01
        $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-01',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '10.000000',
            unitCost: '2.000000',
        )));

        // Create adjustment as of 2026-06-05 when system stock was 10, counted 8 (adjustment -2)
        $adjustment = $this->withTenantExecutionContext($tenantId, fn () => $adjustments->create(new StockAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: '2026-06-05',
            adjustmentType: AdjustmentType::Recount,
            warehouseId: $warehouseId,
            lines: [
                new StockAdjustmentLineData(
                    itemId: (int) $item->getKey(),
                    systemQuantity: '10.000000',
                    countedQuantity: '8.000000',
                    adjustmentQuantity: '-2.000000',
                ),
            ],
        )));

        // Subsequent receipt of 5 on 2026-06-10 (current live stock becomes 15)
        $this->withTenantExecutionContext($tenantId, fn () => $movements->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-10',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '5.000000',
            unitCost: '2.000000',
        )));

        // Posting the adjustment dated 2026-06-05 must succeed because historical stock on June 5 WAS 10!
        $postedAdj = $this->withTenantExecutionContext($tenantId, fn () => $adjustments->post($adjustment));
        $this->assertSame('posted', $postedAdj->status->value);

        // Final live balance should be 15 - 2 = 13
        $availability = $this->withTenantExecutionContext($tenantId, fn () => app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId)));
        $this->assertSame('13.000000', $availability->quantityOnHand);
    }

    public function test_aging_report_point_in_time_calculation(): void
    {
        $tenantId = $this->createTenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);
            $creator = app(InvoiceCreationService::class);
            $statusService = app(InvoiceStatusService::class);
            $agingService = app(AgingReportService::class);

            $customerId = (int) DB::table('customers')->insertGetId([
                'tenant_id' => $tenantId,
                'customer_number' => 'CUS-001',
                'code' => 'CUS-001',
                'name' => 'Test Customer',
                'customer_type' => 'company',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create and post invoice dated 2026-05-01 for $100
            $postingPlan = app(InvoicePostingPlanFactory::class)->outbound(
                FinancePostingProfileCode::SalesInvoice,
                '2026-05-01',
                FinanceAccountRoleCode::Revenue,
                '100.000000',
                description: 'Product sale',
            );

            $invoice = $creator->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Manual,
                direction: InvoiceDocDirection::Outbound,
                invoiceDate: '2026-05-01',
                invoiceNumber: 'INV-AGING-001',
                partyType: 'customer',
                partyId: $customerId,
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Product sale',
                        quantity: '1.000000',
                        unitPrice: '100.000000',
                        sourceLineType: 'manual_line',
                        sourceLineId: 1,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $tenantId,
                        sourceType: 'manual',
                        sourceId: 1,
                        sourceSubtotal: '100.000000',
                        sourceAdjustmentTotal: '0.000000',
                        sourceGrandTotal: '100.000000',
                    ),
                ],
                sourceLines: [
                    new InvoiceSourceLineData(
                        tenantId: $tenantId,
                        sourceType: 'manual',
                        sourceId: 1,
                        sourceLineType: 'manual_line',
                        sourceLineId: 1,
                        sourceQuantity: '1.000000',
                        invoicedQuantity: '1.000000',
                        sourceUnitPrice: '100.000000',
                        sourceLineTotal: '100.000000',
                    ),
                ],
                postingPlan: $postingPlan,
            ));
            $invoice = $statusService->transitionIfVersion($invoice, InvoiceStatus::Approved, $invoice->row_version);
            $invoice = $statusService->transitionIfVersion($invoice, InvoiceStatus::Posted, $invoice->row_version);

            // Allocate a payment on 2026-06-01 for $100
            $pmCode = 'BANK-'.Str::upper(Str::random(4));
            $paymentMethodId = (int) DB::table('payment_methods')->insertGetId([
                'tenant_id' => $tenantId,
                'scope_key' => $tenantId.':global',
                'name' => 'Bank Transfer',
                'code' => $pmCode,
                'method_type' => PaymentMethodType::BankTransfer->value,
                'direction_allowed' => 'both',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            FinancePostingFixture::seedCustomerPaymentProfiles($tenantId);

            $payment = app(PaymentCreationService::class)->create(new CreatePaymentData(
                tenantId: $tenantId,
                direction: PaymentDirection::Inbound,
                paymentType: PaymentDocType::CustomerReceipt,
                partyType: 'customer',
                partyId: $customerId,
                paymentDate: '2026-06-01',
                lines: [
                    new PaymentLineData(
                        paymentMethodId: $paymentMethodId,
                        amount: '100.000000',
                    ),
                ],
                allocations: [
                    new PaymentAllocationData(
                        invoiceId: (int) $invoice->getKey(),
                        allocatedAmount: '100.000000',
                        allocationDate: '2026-06-01',
                    ),
                ],
            ));

            $payment->forceFill(['document_status' => PaymentDocumentStatus::Approved->value])->save();

            app(PaymentPostingService::class)->post($payment, $payment->row_version);

            // Invoice is now fully paid in live table (remaining_amount = 0)
            $invoice->refresh();
            $this->assertSame('0.000000', (string) $invoice->balance->remaining_amount);

            // 1. As of 2026-05-15 (prior to payment), the invoice WAS unpaid:
            // Dynamic point-in-time Aging Report must show the invoice with remaining $100!
            $reportMay = $agingService->receivables($tenantId, null, '2026-05-15');
            $this->assertSame('100.000000', $reportMay['total']);
            $this->assertCount(1, $reportMay['rows']);
            $this->assertSame('100.000000', $reportMay['rows'][0]['remaining_amount']);
            $this->assertSame('INV-AGING-001', $reportMay['rows'][0]['invoice_number']);

            // 2. As of 2026-06-05 (after payment was allocated on June 1), the invoice was fully settled:
            // Aging report must NOT include it!
            $reportJune = $agingService->receivables($tenantId, null, '2026-06-05');
            $this->assertSame('0.000000', $reportJune['total']);
            $this->assertCount(0, $reportJune['rows']);

            // 3. Point-in-time calculation for backdated invoice reversal:
            // Create a second invoice on 2026-05-01 for $200
            $postingPlan2 = app(InvoicePostingPlanFactory::class)->outbound(
                FinancePostingProfileCode::SalesInvoice,
                '2026-05-01',
                FinanceAccountRoleCode::Revenue,
                '200.000000',
                description: 'Product sale 2',
            );
            $invoice2 = $creator->create(new CreateInvoiceData(
                tenantId: $tenantId,
                invoiceType: InvoiceType::Manual,
                direction: InvoiceDocDirection::Outbound,
                invoiceDate: '2026-05-01',
                invoiceNumber: 'INV-AGING-002',
                partyType: 'customer',
                partyId: $customerId,
                lines: [
                    new InvoiceLineData(
                        lineNumber: 1,
                        description: 'Product sale 2',
                        quantity: '1.000000',
                        unitPrice: '200.000000',
                        sourceLineType: 'manual_line',
                        sourceLineId: 10,
                    ),
                ],
                sources: [
                    new InvoiceSourceData(
                        tenantId: $tenantId,
                        sourceType: 'manual',
                        sourceId: 10,
                        sourceSubtotal: '200.000000',
                        sourceAdjustmentTotal: '0.000000',
                        sourceGrandTotal: '200.000000',
                    ),
                ],
                sourceLines: [
                    new InvoiceSourceLineData(
                        tenantId: $tenantId,
                        sourceType: 'manual',
                        sourceId: 10,
                        sourceLineType: 'manual_line',
                        sourceLineId: 10,
                        sourceQuantity: '1.000000',
                        invoicedQuantity: '1.000000',
                        sourceUnitPrice: '200.000000',
                        sourceLineTotal: '200.000000',
                    ),
                ],
                postingPlan: $postingPlan2,
            ));
            $invoice2 = $statusService->transitionIfVersion($invoice2, InvoiceStatus::Approved, $invoice2->row_version);
            $invoice2 = $statusService->transitionIfVersion($invoice2, InvoiceStatus::Posted, $invoice2->row_version);

            // Reversal on business date 2026-05-10
            app(InvoiceReversalService::class)->reverse(
                $invoice2,
                $invoice2->row_version,
                '2026-05-10',
                'Backdated cancellation of bill',
            );

            // Touch invoice2's updated_at to simulate a later background job / modification
            DB::table('invoices')->where('id', $invoice2->getKey())->update(['updated_at' => '2026-11-24 12:00:00']);

            // On 2026-05-05 (before reversal on May 10), invoice2 WAS active and outstanding:
            $reportMay5 = $agingService->receivables($tenantId, null, '2026-05-05');
            $this->assertSame('300.000000', $reportMay5['total']); // invoice 1 ($100) + invoice 2 ($200)

            // On 2026-05-15 (after reversal on May 10, but before May payment on June 1):
            // Invoice 2 is reversed as of May 10, so it must NOT appear! Only invoice 1 ($100) remains!
            $reportMay15 = $agingService->receivables($tenantId, null, '2026-05-15');
            $this->assertSame('100.000000', $reportMay15['total']);
            $this->assertCount(1, $reportMay15['rows']);
            $this->assertSame('INV-AGING-001', $reportMay15['rows'][0]['invoice_number']);
        });
    }

    public function test_model_lifecycle_guardrails_prevent_in_place_mutations_and_deletions_on_posted_documents(): void
    {
        $tenantId = $this->createTenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            $orgUnitId = OrganizationUnitFixture::create([
                'tenant_id' => $tenantId,
                'name' => 'Main Org',
                'code' => 'ORG-MAIN',
            ]);

            // 1. Invoice Guardrail
            $invoice = new Invoice;
            $invoice->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'invoice_number' => 'INV-LIFECYCLE-001',
                'invoice_date' => '2026-05-01',
                'due_date' => '2026-05-30',
                'party_type' => 'customer',
                'party_id' => 1,
                'subtotal' => '100.000000',
                'discount_total' => '0.000000',
                'tax_total' => '0.000000',
                'charge_total' => '0.000000',
                'adjustment_total' => '0.000000',
                'grand_total' => '100.000000',
                'exchange_rate' => '1.000000',
                'invoice_type' => InvoiceType::Manual,
                'direction' => InvoiceDocDirection::Outbound,
                'status' => InvoiceStatus::Posted,
                'posted_at' => now(),
            ]);
            $invoice->save();

            // Deleting posted invoice must throw LogicException
            try {
                $invoice->delete();
                $this->fail('Expected deleting posted invoice to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Only draft invoices can be deleted', $e->getMessage());
            }

            // In-place mutation of financial attributes on posted invoice must throw LogicException
            try {
                $invoice->subtotal = '200.000000';
                $invoice->save();
                $this->fail('Expected mutating posted invoice subtotal to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Historical posted invoice financial attributes are immutable in place', $e->getMessage());
            }

            // 2. Payment Guardrail
            $payment = new Payment;
            $payment->forceFill([
                'tenant_id' => $tenantId,
                'payment_number' => 'PAY-LIFECYCLE-001',
                'payment_type' => PaymentDocType::CustomerReceipt,
                'direction' => PaymentDirection::Inbound,
                'payment_date' => '2026-05-01',
                'total_amount' => '100.000000',
                'allocated_amount' => '0.000000',
                'unapplied_amount' => '100.000000',
                'refunded_amount' => '0.000000',
                'document_status' => PaymentDocumentStatus::Approved,
            ]);
            $payment->save();

            try {
                $payment->delete();
                $this->fail('Expected deleting posted payment to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Only draft payments can be archived', $e->getMessage());
            }

            try {
                $payment->total_amount = '200.000000';
                $payment->save();
                $this->fail('Expected mutating posted payment total to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Historical posted payment financial attributes are immutable in place', $e->getMessage());
            }

            // 3. Payment Allocation Guardrail
            $allocation = new PaymentAllocation;
            $allocation->forceFill([
                'tenant_id' => $tenantId,
                'payment_id' => $payment->getKey(),
                'invoice_id' => $invoice->getKey(),
                'invoice_number_snapshot' => 'INV-LIFECYCLE-001',
                'invoice_total' => '100.000000',
                'invoice_balance_before' => '100.000000',
                'previously_allocated_amount' => '0.000000',
                'allocated_amount' => '50.000000',
                'invoice_balance_after' => '50.000000',
                'allocation_date' => '2026-05-02',
                'invoice_exchange_rate_snapshot' => '1.000000',
            ]);
            $allocation->save();

            try {
                $allocation->delete();
                $this->fail('Expected deleting payment allocation to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Payment allocations cannot be deleted', $e->getMessage());
            }

            try {
                $allocation->allocated_amount = '75.000000';
                $allocation->save();
                $this->fail('Expected mutating payment allocation amount to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Payment allocation amounts and targets are immutable', $e->getMessage());
            }

            // 4. Expense Guardrail
            $expenseTypeId = (int) DB::table('expense_types')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => 'Office',
                'code' => 'OFFICE',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $expense = new Expense;
            $expense->forceFill([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $orgUnitId,
                'expense_type_id' => $expenseTypeId,
                'expense_type_code_snapshot' => 'OFFICE',
                'expense_type_name_snapshot' => 'Office',
                'payment_method_id_snapshot' => 1,
                'payment_method_code_snapshot' => 'CASH',
                'payment_method_name_snapshot' => 'Cash',
                'payment_method_type_snapshot' => 'cash',
                'expense_number' => 'EXP-LIFECYCLE-001',
                'expense_date' => '2026-05-01',
                'amount' => '50.000000',
                'exchange_rate' => '1.000000',
                'status' => ExpenseStatus::Posted,
                'posted_at' => now(),
            ]);
            $expense->save();

            try {
                $expense->delete();
                $this->fail('Expected deleting posted expense to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Expenses cannot be deleted', $e->getMessage());
            }

            try {
                $expense->amount = '100.000000';
                $expense->save();
                $this->fail('Expected mutating posted expense amount to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Posted expenses are immutable in place', $e->getMessage());
            }

            // 5. Goods Receipt Note Guardrail
            $warehouseId = $this->createWarehouse($tenantId);
            $grn = new GoodsReceiptNote;
            $grn->forceFill([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'grn_number' => 'GRN-LIFECYCLE-001',
                'received_date' => '2026-05-01',
                'status' => GoodsReceiptNoteStatus::Posted,
                'subtotal' => '100.000000',
                'grand_total' => '100.000000',
                'posted_at' => now(),
            ]);
            $grn->save();

            try {
                $grn->delete();
                $this->fail('Expected deleting posted GRN to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Only draft goods receipt notes can be deleted', $e->getMessage());
            }

            try {
                $grn->grand_total = '150.000000';
                $grn->save();
                $this->fail('Expected mutating posted GRN grand total to throw LogicException.');
            } catch (LogicException $e) {
                $this->assertStringContainsString('Posted goods receipt note attributes are immutable in place', $e->getMessage());
            }
        });
    }

    public function test_grn_reversal_propagates_reversal_date_to_inventory_and_finance(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId);
        $item = $this->createItem($tenantId, 'ITEM-GRN-REV');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            FinancePostingFixture::seedSupplierInvoiceProfiles($tenantId);

            $userId = (int) DB::table('users')->insertGetId([
                'tenant_id' => $tenantId,
                'first_name' => 'Purchasing',
                'last_name' => 'Agent',
                'email' => 'purchasing@example.com',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $supplierId = (int) DB::table('suppliers')->insertGetId([
                'tenant_id' => $tenantId,
                'supplier_number' => 'SUP-001',
                'code' => 'SUP-001',
                'name' => 'Test Supplier',
                'supplier_type' => 'company',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $grnService = app(GoodsReceiptNoteService::class);
            $coordinator = app(PurchaseGoodsReceiptPostingCoordinator::class);

            // Create and post GRN on 2026-05-01
            $grn = $grnService->create(new CreateGoodsReceiptNoteData(
                tenantId: $tenantId,
                receivedDate: '2026-05-01',
                warehouseId: $warehouseId,
                supplierId: $supplierId,
                lines: [
                    new GoodsReceiptNoteLineData(
                        itemId: (int) $item->getKey(),
                        uomId: (int) $item->base_uom_id,
                        receivedQuantity: '10.000000',
                        acceptedQuantity: '10.000000',
                        unitPrice: '15.000000',
                    ),
                ],
            ));
            $postedGrn = $coordinator->post($grn, $userId);
            $this->assertSame(GoodsReceiptNoteStatus::Posted, $postedGrn->status);

            // Verify original inventory receipt movement has movement_date = '2026-05-01'
            $receiptMovement = DB::table('inventory_movements')
                ->where('tenant_id', $tenantId)
                ->where('item_id', $item->getKey())
                ->where('direction', 'in')
                ->first();
            $this->assertNotNull($receiptMovement);
            $this->assertSame('2026-05-01', substr((string) $receiptMovement->movement_date, 0, 10));

            // Reverse GRN on civil business date 2026-05-15
            $reversedGrn = $coordinator->reverse(
                $postedGrn,
                '2026-05-15',
                'Goods defective on inspection',
                $userId,
                $postedGrn->row_version,
            );
            $this->assertSame(GoodsReceiptNoteStatus::Reversed, $reversedGrn->status);

            // Verify that the inventory reversal movement received the EXACT reversal business date (2026-05-15)
            // and did NOT fall back to received_date (2026-05-01)!
            $reversalMovement = DB::table('inventory_movements')
                ->where('tenant_id', $tenantId)
                ->where('item_id', $item->getKey())
                ->where('direction', 'out')
                ->first();
            $this->assertNotNull($reversalMovement);
            $this->assertSame('2026-05-15', substr((string) $reversalMovement->movement_date, 0, 10));

            // Verify that the finance journal reversal entry also has journal_date = '2026-05-15'
            $journalReversal = DB::table('finance_journal_entries')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('reversal_of_id')
                ->first();
            $this->assertNotNull($journalReversal);
            $this->assertSame('2026-05-15', substr((string) $journalReversal->journal_date, 0, 10));
        });
    }

    public function test_aging_report_accounts_for_backdated_credit_allocations_via_operational_date(): void
    {
        $tenantId = $this->createTenant();
        $this->withTenantExecutionContext($tenantId, function () use ($tenantId): void {
            FinancePostingFixture::seedCustomerInvoiceProfiles($tenantId);
            $agingService = app(AgingReportService::class);

            $customerId = (int) DB::table('customers')->insertGetId([
                'tenant_id' => $tenantId,
                'customer_number' => 'CUS-CREDIT-01',
                'code' => 'CUS-CREDIT-01',
                'name' => 'Credit Customer',
                'customer_type' => 'company',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create invoice on 2026-05-01 for $100
            $invoiceId = (int) DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => 'INV-CREDIT-001',
                'invoice_date' => '2026-05-01',
                'due_date' => '2026-05-30',
                'party_type' => 'customer',
                'party_id' => $customerId,
                'subtotal' => '100.000000',
                'discount_total' => '0.000000',
                'tax_total' => '0.000000',
                'charge_total' => '0.000000',
                'adjustment_total' => '0.000000',
                'grand_total' => '100.000000',
                'exchange_rate' => '1.000000',
                'invoice_type' => 'manual',
                'direction' => 'outbound',
                'status' => 'posted',
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ]);

            DB::table('invoice_balances')->insert([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoiceId,
                'invoice_total' => '100.000000',
                'paid_amount' => '0.000000',
                'credit_allocated_amount' => '40.000000',
                'remaining_amount' => '60.000000',
                'status' => 'partially_paid',
                'created_at' => '2026-05-01 10:00:00',
                'updated_at' => '2026-05-01 10:00:00',
            ]);

            // Create a credit note invoice with operational invoice_date = '2026-05-20'
            $creditInvoiceId = (int) DB::table('invoices')->insertGetId([
                'tenant_id' => $tenantId,
                'invoice_number' => 'INV-CN-001',
                'invoice_date' => '2026-05-20',
                'due_date' => '2026-05-20',
                'party_type' => 'customer',
                'party_id' => $customerId,
                'subtotal' => '40.000000',
                'discount_total' => '0.000000',
                'tax_total' => '0.000000',
                'charge_total' => '0.000000',
                'adjustment_total' => '0.000000',
                'grand_total' => '40.000000',
                'exchange_rate' => '1.000000',
                'invoice_type' => 'credit_note',
                'direction' => 'outbound',
                'status' => 'posted',
                'created_at' => '2026-05-02 10:00:00', // physical timestamp was early!
                'updated_at' => '2026-05-02 10:00:00',
            ]);

            // Create invoice credit allocation
            DB::table('invoice_credit_allocations')->insert([
                'tenant_id' => $tenantId,
                'credit_source_type' => 'invoice',
                'credit_source_id' => $creditInvoiceId,
                'invoice_id' => $invoiceId,
                'invoice_total' => '100.000000',
                'previously_allocated_amount' => '0.000000',
                'allocated_amount' => '40.000000',
                'remaining_invoice_balance' => '60.000000',
                'created_at' => '2026-05-02 10:00:00', // physical timestamp
                'updated_at' => '2026-05-02 10:00:00',
            ]);

            // 1. As of 2026-05-10:
            // Even though created_at was 2026-05-02, the return operational date is 2026-05-20.
            // So on May 10, the credit is NOT effective yet! Remaining amount must be $100!
            $reportMay10 = $agingService->receivables($tenantId, null, '2026-05-10');
            $this->assertSame('100.000000', $reportMay10['total']);
            $this->assertCount(1, $reportMay10['rows']);
            $this->assertSame('100.000000', $reportMay10['rows'][0]['remaining_amount']);

            // 2. As of 2026-05-25:
            // The return date 2026-05-20 has passed. The credit is now effective! Remaining amount must be $60!
            $reportMay25 = $agingService->receivables($tenantId, null, '2026-05-25');
            $this->assertSame('60.000000', $reportMay25['total']);
            $this->assertCount(1, $reportMay25['rows']);
            $this->assertSame('60.000000', $reportMay25['rows'][0]['remaining_amount']);
        });
    }

    public function test_uom_revision_reversal_uses_operational_movement_date_rather_than_created_at(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId);
        $item = $this->createItem($tenantId, 'ITEM-UOM-REV');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $uomBoxId = (int) DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'BOX-'.Str::upper(Str::random(3)),
                'name' => 'Box',
                'symbol' => 'box',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $movements = app(StockMovementService::class);

            // 1. Initial stock receipt of 50
            $movements->record(new StockMovementData(
                tenantId: $tenantId,
                movementDate: '2026-04-01',
                movementType: InventoryMovementType::Receipt,
                direction: InventoryDirection::In,
                itemId: (int) $item->getKey(),
                warehouseId: $warehouseId,
                quantity: '50.000000',
                unitCost: '5.000000',
            ));

            // Record issue movement with operational movement_date = '2026-05-01'
            $movement = $movements->record(new StockMovementData(
                tenantId: $tenantId,
                movementDate: '2026-05-01',
                movementType: InventoryMovementType::Issue,
                direction: InventoryDirection::Out,
                itemId: (int) $item->getKey(),
                warehouseId: $warehouseId,
                quantity: '10.000000',
                unitCost: '5.000000',
            ));

            // Force created_at to a future physical date (2026-05-25) to simulate late ingestion
            DB::table('inventory_movements')->where('id', $movement->getKey())->update([
                'created_at' => '2026-05-25 12:00:00',
            ]);
            $movement->refresh();

            // 2. Create an applied ItemBaseUomRevision on 2026-05-10 (between movement_date May 1 and created_at May 25!)
            // Old UOM: $item->base_uom_id, New UOM: $uomBoxId, factor = 2.000000 (1 box = 2 pcs)
            DB::table('item_base_uom_revisions')->insert([
                'tenant_id' => $tenantId,
                'item_id' => (int) $item->getKey(),
                'old_base_uom_id' => (int) $item->base_uom_id,
                'new_base_uom_id' => $uomBoxId,
                'conversion_factor' => '2.000000',
                'status' => 'applied',
                'effective_at' => '2026-05-10 09:00:00',
                'applied_at' => '2026-05-10 09:00:00',
                'reason' => 'Package size change',
                'created_at' => '2026-05-10 09:00:00',
                'updated_at' => '2026-05-10 09:00:00',
            ]);

            // Update item to use new base UOM
            $item->base_uom_id = $uomBoxId;
            $item->save();

            // 3. Reversing the issue movement must succeed using the revision applied on May 10
            // Because movement_date was May 1 (before revision May 10), even though created_at was May 25!
            $reversed = $movements->reverse($movement);
            $this->assertSame('reversed', $movement->refresh()->status->value);
            // In new base UOM: quantity is 10 * 2 = 20
            $this->assertSame('20.000000', (string) $reversed->quantity);
        });
    }

    private function createTenant(): int
    {
        $suffix = Str::upper(Str::random(4));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-'.$suffix,
            'name' => 'Tenant '.$suffix,
            'slug' => 'tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWarehouse(int $tenantId): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'code' => 'WH-'.Str::upper(Str::random(4)),
            'name' => 'Warehouse Main',
            'type' => 'standard',
            'is_active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createItem(
        int $tenantId,
        string $code,
        TrackingType $tracking = TrackingType::None,
        CostingMethod $costing = CostingMethod::Fifo,
        ItemType $type = ItemType::Stock,
    ): Item {
        return $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $code, $tracking, $costing, $type): Item {
            $uomId = (int) DB::table('unit_of_measures')->insertGetId([
                'tenant_id' => $tenantId,
                'code' => 'PCS-'.Str::upper(Str::random(3)),
                'name' => 'Pieces',
                'symbol' => 'pcs',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $item = new Item;
            $item->forceFill([
                'tenant_id' => $tenantId,
                'code' => $code,
                'name' => 'Item '.$code,
                'item_type' => $type,
                'costing_method' => $costing,
                'tracking_type' => $tracking,
                'base_uom_id' => $uomId,
                'is_stockable' => true,
                'is_active' => true,
            ]);
            $item->save();

            return $item;
        });
    }
}
