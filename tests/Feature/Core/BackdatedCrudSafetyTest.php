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
use Modules\Payment\Models\PaymentLine;
use Modules\Payment\Services\PaymentCashReceiptDatePolicy;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Services\Tax\GoodsReceiptNoteTaxDocumentMapper;
use Tests\Support\FinancePostingFixture;
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
