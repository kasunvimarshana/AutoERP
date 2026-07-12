<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\AdjustmentType as InventoryAdjustmentType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\CreatePurchaseDebitNoteData;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\DTOs\PurchaseInvoiceSourceData;
use Modules\Purchase\DTOs\PurchaseOrderLineData;
use Modules\Purchase\DTOs\PurchaseReturnLineData;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationBase;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Enums\PurchaseDebitNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Enums\PurchaseReturnStatus;
use Modules\Purchase\Enums\PurchaseReturnType;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseReturnLine;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\GoodsReceiptNoteService;
use Modules\Purchase\Services\PurchaseDebitNoteService;
use Modules\Purchase\Services\PurchaseFinancePreparationService;
use Modules\Purchase\Services\PurchaseInvoiceIntegrationService;
use Modules\Purchase\Services\PurchaseOrderService;
use Modules\Purchase\Services\PurchasePaymentIntegrationService;
use Modules\Purchase\Services\PurchaseReturnService;
use Tests\Support\FinancePostingFixture;
use Tests\TestCase;

final class PurchaseEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_creation_totals_with_header_adjustments(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();

        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);

        $this->assertSame(PurchaseOrderStatus::Draft, $order->status);
        $this->assertSame('100000.000000', (string) $order->subtotal);
        $this->assertSame('5000.000000', (string) $order->discount_total);
        $this->assertSame('18000.000000', (string) $order->tax_total);
        $this->assertSame('10000.000000', (string) $order->charge_total);
        $this->assertSame('123000.000000', (string) $order->grand_total);
        $this->assertCount(2, $order->adjustments);
    }

    public function test_purchase_permission_descriptions_exclude_unsupported_workflows(): void
    {
        $permissions = PurchaseAuthorizationService::descriptions();

        foreach ([
            'purchase.goods_receipts.update',
            'purchase.goods_receipts.cancel',
            'purchase.supplier_invoices.update',
            'purchase.supplier_invoices.post',
            'purchase.supplier_invoices.cancel',
            'purchase.returns.update',
            'purchase.returns.reverse',
            'purchase.debit_notes.update',
            'purchase.debit_notes.reverse',
            'purchase.debit_notes.cancel',
        ] as $permission) {
            $this->assertArrayNotHasKey($permission, $permissions);
        }
    }

    public function test_line_and_header_percentage_adjustments_and_uom_conversion(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $pcsUomId] = $this->purchaseContext();
        $boxUomId = $this->createUom($tenantId, 'BOX-'.Str::upper(Str::random(4)), false);
        DB::table('item_units')->insert([
            'tenant_id' => $tenantId,
            'item_id' => $item->getKey(),
            'uom_id' => $boxUomId,
            'unit_role' => 'purchase',
            'conversion_factor' => '12.000000',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = $this->createPurchaseOrder(new CreatePurchaseOrderData(
            tenantId: $tenantId,
            purchaseOrderDate: '2026-06-06',
            supplierType: 'supplier',
            supplierId: $supplierId,
            warehouseId: $warehouseId,
            lines: [new PurchaseOrderLineData(
                itemId: (int) $item->getKey(),
                orderedQuantity: '10.000000',
                unitPrice: '100.000000',
                uomId: $boxUomId,
                discountCalculationType: PurchaseAdjustmentCalculationType::Percentage,
                discountRate: '10.000000',
                taxCalculationType: PurchaseAdjustmentCalculationType::Percentage,
                taxRate: '15.000000',
                chargeCalculationType: PurchaseAdjustmentCalculationType::Fixed,
                chargeAmount: '25.000000',
            )],
            adjustments: [new PurchaseHeaderAdjustmentData(
                name: 'Service',
                adjustmentType: PurchaseAdjustmentType::ServiceCharge,
                effect: PurchaseAdjustmentEffect::Increase,
                amount: '0.000000',
                calculationType: PurchaseAdjustmentCalculationType::Percentage,
                calculationBase: PurchaseAdjustmentCalculationBase::SubtotalAfterLineDiscount,
                rate: '5.000000',
            )],
        ));

        $line = $order->lines->first();
        $this->assertSame('120.000000', (string) $line->base_quantity);
        $this->assertSame('100.000000', (string) $line->discount_amount);
        $this->assertSame('135.000000', (string) $line->tax_amount);
        $this->assertSame('45.000000', (string) $order->adjustments->first()->amount);
        $this->assertSame('1105.000000', (string) $order->grand_total);
    }

    public function test_partial_grn_posts_inventory_and_skips_service_items(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $service = $this->createItem($tenantId, 'SVC-'.Str::upper(Str::random(4)), ItemType::Service, false, $uomId);
        $order = $this->createPurchaseOrder(new CreatePurchaseOrderData(
            tenantId: $tenantId,
            purchaseOrderDate: '2026-06-06',
            supplierType: 'supplier',
            supplierId: $supplierId,
            warehouseId: $warehouseId,
            lines: [
                new PurchaseOrderLineData((int) $item->getKey(), '10.000000', '100.000000', uomId: $uomId),
                new PurchaseOrderLineData((int) $service->getKey(), '1.000000', '50.000000', uomId: $uomId),
            ],
        ));
        $order = $this->approveOrder($order);

        $grn = $this->createGoodsReceiptNote(new CreateGoodsReceiptNoteData(
            tenantId: $tenantId,
            receivedDate: '2026-06-06',
            warehouseId: $warehouseId,
            purchaseOrderId: (int) $order->getKey(),
            lines: [
                new GoodsReceiptNoteLineData((int) $item->getKey(), '4.000000', '4.000000', '100.000000', purchaseOrderLineId: (int) $order->lines[0]->getKey(), orderedQuantity: '10.000000'),
                new GoodsReceiptNoteLineData((int) $service->getKey(), '1.000000', '1.000000', '50.000000', purchaseOrderLineId: (int) $order->lines[1]->getKey(), orderedQuantity: '1.000000'),
            ],
        ));

        $posted = $this->postGoodsReceiptNote($grn);

        $this->assertSame(GoodsReceiptNoteStatus::Posted, $posted->status);
        $movementCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => InventoryMovement::query()->where('source_type', 'goods_receipt_note')->count(),
        );
        $availability = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId)),
        );
        $this->assertSame(1, $movementCount);
        $this->assertSame('4.000000', $availability->quantityOnHand);
        $orderStatus = $this->withTenantExecutionContext(
            $tenantId,
            fn (): PurchaseOrderStatus => $order->refresh()->status,
        );
        $receivedQuantity = $this->withTenantExecutionContext(
            $tenantId,
            fn (): string => (string) $order->lines()->where('item_id', $item->getKey())->firstOrFail()->received_quantity,
        );
        $this->assertSame(PurchaseOrderStatus::Approved, $orderStatus);
        $this->assertSame('4.000000', $receivedQuantity);
    }

    public function test_many_grns_can_create_one_supplier_invoice_with_header_adjustments(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grnOne, $grnTwo] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);

        $invoice = $this->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: $tenantId,
            invoiceDate: '2026-06-06',
            supplierType: 'supplier',
            supplierId: (int) $order->supplier_id,
            sources: [
                new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grnOne->getKey()),
                new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grnTwo->getKey()),
            ],
        ));

        $this->assertSame('123000.000000', (string) $invoice->grand_total);
        $invoiceLinkCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => PurchaseInvoiceLink::query()->where('invoice_id', $invoice->getKey())->count(),
        );
        $this->assertSame(2, $invoiceLinkCount);
        $this->assertCount(4, $invoice->adjustmentAllocations);
    }

    public function test_purchase_invoice_sources_must_match_the_selected_supplier(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        $order = $this->approveOrder($order);
        $otherSupplierId = $this->createSupplier(
            $tenantId,
            'SUP-OTHER-'.Str::upper(Str::random(4)),
        );

        try {
            $this->createSupplierInvoice(
                new CreatePurchaseInvoiceData(
                    tenantId: $tenantId,
                    invoiceDate: '2026-06-06',
                    supplierType: 'supplier',
                    supplierId: $otherSupplierId,
                    sources: [
                        new PurchaseInvoiceSourceData(
                            'purchase_order',
                            (int) $order->getKey(),
                        ),
                    ],
                ),
            );
            $this->fail('Expected selected supplier validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'All purchase invoice sources must belong to the selected supplier.',
                $exception->errors()['supplier_id'][0] ?? null,
            );
        }
    }

    public function test_one_grn_can_be_invoiced_by_many_supplier_invoices(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();

        $invoiceOne = $this->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: $tenantId,
            invoiceDate: '2026-06-06',
            sources: [new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grn->getKey(), [$lineId => '4.000000'])],
        ));

        $invoiceTwo = $this->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: $tenantId,
            invoiceDate: '2026-06-07',
            sources: [new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grn->getKey())],
        ));

        $this->assertSame('4920.000000', (string) $invoiceOne->grand_total);
        $this->assertSame('44280.000000', (string) $invoiceTwo->grand_total);
        $this->assertSame(GoodsReceiptNoteStatus::Posted, $grn->refresh()->status);
        $invoicedQuantity = $this->withTenantExecutionContext(
            $tenantId,
            fn (): string => (string) $grn->lines()->firstOrFail()->invoiced_quantity,
        );
        $this->assertSame('40.000000', $invoicedQuantity);
    }

    public function test_example_40_percent_grn_invoice_allocates_adjustments(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);

        $invoice = $this->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: $tenantId,
            invoiceDate: '2026-06-06',
            sources: [new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grn->getKey())],
        ));

        $this->assertSame('40000.000000', (string) $invoice->subtotal);
        $this->assertSame('2000.000000', (string) $invoice->discount_total);
        $this->assertSame('7200.000000', (string) $invoice->tax_total);
        $this->assertSame('4000.000000', (string) $invoice->charge_total);
        $this->assertSame('49200.000000', (string) $invoice->grand_total);
        $adjustmentCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => PurchaseHeaderAdjustment::query()->where('source_type', 'goods_receipt_note')->where('source_id', $grn->getKey())->count(),
        );
        $this->assertSame(2, $adjustmentCount);
    }

    public function test_partial_receipt_partial_invoice_and_supplier_payment_preparation_workflow(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();

        $invoice = $this->createSupplierInvoice(
            new CreatePurchaseInvoiceData(
                tenantId: $tenantId,
                invoiceDate: '2026-06-08',
                supplierType: 'supplier',
                supplierId: $supplierId,
                sources: [
                    new PurchaseInvoiceSourceData(
                        'goods_receipt_note',
                        (int) $grn->getKey(),
                        [$lineId => '20.000000'],
                    ),
                ],
            ),
        );
        $invoice = $this->postInvoice($invoice);

        $paymentMethodId = $this->paymentMethod($tenantId, 'PARTIAL');
        $payment = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PurchasePaymentIntegrationService::class)->prepareSupplierPayment(
                tenantId: $tenantId,
                paymentDate: '2026-06-09',
                amount: (string) $invoice->grand_total,
                supplierType: 'supplier',
                supplierId: $supplierId,
                lines: [new PaymentLineData((string) $invoice->grand_total, $paymentMethodId, 'PAY-PARTIAL')],
                allocations: [
                    new PaymentAllocationData(
                        (int) $invoice->getKey(),
                        (string) $invoice->grand_total,
                        '2026-06-09',
                    ),
                ],
            ),
        );

        $documentState = $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => [
                'grn_status' => $grn->refresh()->status,
                'order_status' => $order->refresh()->status,
                'invoiced_quantity' => (string) $grn->lines()->firstOrFail()->invoiced_quantity,
            ],
        );
        $this->assertSame(GoodsReceiptNoteStatus::Posted, $documentState['grn_status']);
        $this->assertSame(PurchaseOrderStatus::Approved, $documentState['order_status']);
        $this->assertSame('20.000000', $documentState['invoiced_quantity']);
        $this->assertSame(PaymentType::SupplierPayment, $payment->paymentType);
        $this->assertSame(PaymentDirection::Outbound, $payment->direction);
        $this->assertSame((int) $invoice->getKey(), $payment->allocations[0]->invoiceId);
        $this->assertSame((string) $invoice->grand_total, $payment->allocations[0]->allocatedAmount);
    }

    public function test_supplier_payment_creation_persists_payment_and_allocation(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $this->fakeFinancePosting();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();

        $invoice = $this->createSupplierInvoice(
            new CreatePurchaseInvoiceData(
                tenantId: $tenantId,
                invoiceDate: '2026-06-08',
                supplierType: 'supplier',
                supplierId: $supplierId,
                sources: [
                    new PurchaseInvoiceSourceData(
                        'goods_receipt_note',
                        (int) $grn->getKey(),
                        [$lineId => '20.000000'],
                    ),
                ],
            ),
        );
        $invoice = $this->postInvoice($invoice);
        $paymentMethodId = $this->paymentMethod($tenantId, 'PURCHASE');

        $payment = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PurchasePaymentIntegrationService::class)->createSupplierPayment(
                tenantId: $tenantId,
                paymentDate: '2026-06-09',
                amount: (string) $invoice->grand_total,
                supplierType: 'supplier',
                supplierId: $supplierId,
                lines: [new PaymentLineData((string) $invoice->grand_total, $paymentMethodId, 'PAY-PURCHASE')],
                allocations: [
                    new PaymentAllocationData(
                        (int) $invoice->getKey(),
                        (string) $invoice->grand_total,
                        '2026-06-09',
                    ),
                ],
            ),
        );

        $paymentCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => Payment::query()->count(),
        );
        $this->assertSame(1, $paymentCount);
        $invoiceBalanceDue = $this->withTenantExecutionContext(
            $tenantId,
            fn (): string => (string) $invoice->refresh()->balance_due,
        );
        $this->assertSame((string) $invoice->grand_total, (string) $payment->total_amount);
        $this->assertSame((string) $invoice->grand_total, (string) $payment->allocated_amount);
        $this->assertSame('0.000000', $invoiceBalanceDue);
        $this->assertSame('active', (string) $payment->allocations->firstOrFail()->status->value);
    }

    public function test_supplier_payment_preview_is_non_persistent_and_preserves_invoice_balance(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();
        $invoice = $this->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: $tenantId,
            invoiceDate: '2026-06-08',
            supplierType: 'supplier',
            supplierId: $supplierId,
            sources: [new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grn->getKey(), [$lineId => '20.000000'])],
        ));
        $invoice = $this->postInvoice($invoice);
        $paymentMethodId = $this->paymentMethod($tenantId, 'PREVIEW');
        $balanceBefore = (string) $invoice->refresh()->balance_due;

        for ($i = 0; $i < 2; $i++) {
            $preview = $this->withTenantExecutionContext(
                $tenantId,
                fn () => app(PurchasePaymentIntegrationService::class)->previewSupplierPayment(
                    tenantId: $tenantId,
                    paymentDate: '2026-06-09',
                    amount: (string) $invoice->grand_total,
                    supplierType: 'supplier',
                    supplierId: $supplierId,
                    lines: [new PaymentLineData((string) $invoice->grand_total, $paymentMethodId, 'PAY-PREVIEW')],
                    allocations: [new PaymentAllocationData((int) $invoice->getKey(), (string) $invoice->grand_total, '2026-06-09')],
                ),
            );

            $this->assertSame((string) $invoice->grand_total, $preview->allocationTotal);
            $this->assertSame('0.000000', $preview->unappliedAmount);
        }

        $paymentCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => Payment::query()->count(),
        );
        $this->assertSame(0, $paymentCount);
        $this->assertSame($balanceBefore, (string) $invoice->refresh()->balance_due);
    }

    public function test_purchase_return_approval_matrix_respects_approval_required(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();
        $noApproval = $this->createPurchaseReturn(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-10',
            warehouseId: $warehouseId,
            supplierType: 'supplier',
            supplierId: $supplierId,
            sourceType: 'goods_receipt_note',
            sourceId: (int) $grn->getKey(),
            approvalRequired: false,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', $lineId, '5.000000')],
        ));

        try {
            $this->approvePurchaseReturn($noApproval);
            $this->fail('Expected non-approval return approval to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Purchase return does not require approval.', $exception->getMessage());
        }

        $postedWithoutApproval = $this->postPurchaseReturn($noApproval);
        $this->assertSame(PurchaseReturnStatus::Posted->value, $postedWithoutApproval->status);

        $approvalRequired = $this->createPurchaseReturn(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-11',
            warehouseId: $warehouseId,
            supplierType: 'supplier',
            supplierId: $supplierId,
            reason: 'Manual supplier return',
            returnType: PurchaseReturnType::ManualSupplierReturn,
            costBasis: '10.000000',
            approvalRequired: false,
            lines: [new PurchaseReturnLineData(null, null, '1.000000', itemId: (int) $item->getKey(), uomId: $uomId, costBasis: '10.000000', clientLineKey: 'manual-approval-line')],
        ));

        try {
            $this->postPurchaseReturn($approvalRequired);
            $this->fail('Expected approval-required return posting to fail before approval.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Purchase return must be approved before posting.', $exception->getMessage());
        }

        $approved = $this->approvePurchaseReturn($approvalRequired);
        $this->assertSame(PurchaseReturnStatus::Approved, $approved->status);
        $this->assertTrue((bool) $approved->approval_required);
    }

    public function test_sequential_double_invoicing_is_prevented(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();

        $this->createSupplierInvoice(
            new CreatePurchaseInvoiceData(
                tenantId: $tenantId,
                invoiceDate: '2026-06-08',
                sources: [
                    new PurchaseInvoiceSourceData(
                        'goods_receipt_note',
                        (int) $grn->getKey(),
                    ),
                ],
            ),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Purchase invoice quantity cannot exceed GRN remaining procurement quantity.',
        );

        $this->createSupplierInvoice(
            new CreatePurchaseInvoiceData(
                tenantId: $tenantId,
                invoiceDate: '2026-06-09',
                sources: [
                    new PurchaseInvoiceSourceData(
                        'goods_receipt_note',
                        (int) $grn->getKey(),
                        [$lineId => '1.000000'],
                    ),
                ],
            ),
        );
    }

    public function test_purchase_invoice_rejects_po_and_linked_grn_line_overlap(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        $order = $this->approveOrder($order);
        $poLineId = (int) $order->lines->first()->getKey();
        $grn = $this->createGoodsReceiptNote(new CreateGoodsReceiptNoteData(
            tenantId: $tenantId,
            receivedDate: '2026-06-06',
            warehouseId: $warehouseId,
            purchaseOrderId: (int) $order->getKey(),
            lines: [new GoodsReceiptNoteLineData((int) $item->getKey(), '40.000000', '40.000000', '1000.000000', purchaseOrderLineId: $poLineId, orderedQuantity: '100.000000')],
        ));
        $grn = $this->postGoodsReceiptNote($grn);
        $grn = $this->withTenantExecutionContext($tenantId, fn (): GoodsReceiptNote => $grn->load('lines'));
        $grnLineId = (int) $grn->lines->first()->getKey();

        try {
            $this->previewSupplierInvoice(new CreatePurchaseInvoiceData(
                tenantId: $tenantId,
                invoiceDate: '2026-06-08',
                sources: [
                    new PurchaseInvoiceSourceData('purchase_order', (int) $order->getKey(), [$poLineId => '20.000000']),
                    new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grn->getKey(), [$grnLineId => '20.000000']),
                ],
            ));
            $this->fail('Expected PO/GRN lineage overlap validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Purchase invoice cannot mix a purchase order line with goods receipt lines derived from the same purchase order line.',
                $exception->getMessage(),
            );
        }
    }

    public function test_standalone_purchase_grn_requires_supplier(): void
    {
        [$tenantId, $warehouseId, $item, , $uomId] = $this->purchaseContext();

        try {
            $this->createGoodsReceiptNote(new CreateGoodsReceiptNoteData(
                tenantId: $tenantId,
                receivedDate: '2026-06-06',
                warehouseId: $warehouseId,
                lines: [new GoodsReceiptNoteLineData((int) $item->getKey(), '1.000000', '1.000000', '10.000000', uomId: $uomId)],
            ));
            $this->fail('Expected standalone GRN supplier validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Standalone Purchase GRNs require a supplier.', $exception->getMessage());
        }
    }

    public function test_supplier_advance_can_be_allocated_after_purchase_invoice_creation(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId] = $this->purchaseContext();
        FinancePostingFixture::seedSupplierPaymentProfiles($tenantId);
        $advance = $this->createPayment(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::Advance,
            direction: PaymentDirection::Outbound,
            paymentDate: '2026-06-05',
            partyType: 'supplier',
            partyId: $supplierId,
            lines: [new PaymentLineData(
                amount: '60000.000000',
                paymentMethodId: $this->paymentMethod($tenantId, 'ADVANCE'),
            )],
        ));
        $advance = $this->withTenantExecutionContext($tenantId, function () use ($advance): Payment {
            $lifecycle = app(PaymentDocumentLifecycleService::class);
            $advance = $lifecycle->submit($advance, (int) $advance->row_version);
            $advance = $lifecycle->approve($advance, (int) $advance->row_version);

            return app(PaymentPostingService::class)->post($advance, (int) $advance->row_version);
        });

        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $invoice = $this->createSupplierInvoice(
            new CreatePurchaseInvoiceData(
                tenantId: $tenantId,
                invoiceDate: '2026-06-08',
                supplierType: 'supplier',
                supplierId: $supplierId,
                sources: [
                    new PurchaseInvoiceSourceData(
                        'goods_receipt_note',
                        (int) $grn->getKey(),
                    ),
                ],
            ),
        );
        $invoice = $this->postInvoice($invoice);

        $advance = $this->allocatePayment(
            $advance,
            [
                new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: (string) $invoice->grand_total,
                    allocationDate: '2026-06-09',
                ),
            ],
            (int) $advance->row_version,
        );

        $this->assertSame(PaymentDocumentStatus::Approved, $advance->document_status);
        $this->assertSame(PaymentPostingStatus::Posted, $advance->posting_status);
        $this->assertSame(PaymentAllocationState::PartiallyAllocated, $advance->allocation_status);
        $this->assertSame('49200.000000', (string) $advance->allocated_amount);
        $this->assertSame('10800.000000', (string) $advance->unapplied_amount);
        $remainingAmount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): string => (string) $invoice->refresh()->balance->remaining_amount,
        );
        $this->assertSame('0.000000', $remainingAmount);
    }

    public function test_cancelled_purchase_invoice_restores_grn_and_order_invoiceable_quantity(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $invoice = $this->createSupplierInvoice(
            new CreatePurchaseInvoiceData(
                tenantId: $tenantId,
                invoiceDate: '2026-06-08',
                sources: [
                    new PurchaseInvoiceSourceData(
                        'goods_receipt_note',
                        (int) $grn->getKey(),
                    ),
                ],
            ),
        );

        $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(InvoiceStatusService::class)->transition($invoice, InvoiceStatus::Cancelled),
        );

        [$grn, $order] = $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => [$grn->refresh()->load('lines'), $order->refresh()->load('lines')],
        );
        $this->assertSame(GoodsReceiptNoteStatus::Posted, $grn->status);
        $this->assertSame('0.000000', (string) $grn->lines->first()->invoiced_quantity);
        $this->assertSame('40.000000', (string) $grn->lines->first()->remaining_quantity);
        $this->assertSame('0.000000', (string) $order->lines->first()->invoiced_quantity);
        $this->assertSame('100.000000', (string) $order->lines->first()->remaining_invoiceable_quantity);
        $linkStatus = $this->withTenantExecutionContext(
            $tenantId,
            fn (): ?string => PurchaseInvoiceLink::query()->where('invoice_id', $invoice->getKey())->value('status'),
        );
        $this->assertSame('cancelled', $linkStatus);

        $replacement = $this->createSupplierInvoice(
            new CreatePurchaseInvoiceData(
                tenantId: $tenantId,
                invoiceDate: '2026-06-09',
                sources: [
                    new PurchaseInvoiceSourceData(
                        'goods_receipt_note',
                        (int) $grn->getKey(),
                    ),
                ],
            ),
        );
        $this->assertSame('49200.000000', (string) $replacement->grand_total);
    }

    public function test_purchase_debit_note_supports_partial_and_full_invoice_allocation(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $invoice = $this->createSupplierInvoice(
            new CreatePurchaseInvoiceData(
                tenantId: $tenantId,
                invoiceDate: '2026-06-08',
                supplierType: 'supplier',
                supplierId: $supplierId,
                sources: [
                    new PurchaseInvoiceSourceData(
                        'goods_receipt_note',
                        (int) $grn->getKey(),
                    ),
                ],
            ),
        );
        $invoice = $this->postInvoice($invoice);
        $this->assertSame('posted', DB::table('invoices')->where('id', $invoice->getKey())->value('status'));

        $note = $this->createDebitNote(new CreatePurchaseDebitNoteData(
            tenantId: $tenantId,
            debitNoteDate: '2026-06-09',
            amount: '20.000000',
            supplierType: 'supplier',
            supplierId: $supplierId,
            reason: 'Price dispute',
        ));
        $note = $this->withTenantExecutionContext($tenantId, function () use ($note, $invoice): PurchaseDebitNote {
            $debitNotes = app(PurchaseDebitNoteService::class);
            $note = $debitNotes->approve($note);
            $note = $debitNotes->post($note);

            return $debitNotes->allocate($note, $invoice, '8.000000');
        });

        $this->assertSame(PurchaseDebitNoteStatus::Posted, $note->status);
        $this->assertSame('12.000000', (string) $note->remaining_amount);
        $this->assertSame('49192.000000', (string) $invoice->refresh()->balance_due);

        $note = $this->withTenantExecutionContext(
            $tenantId,
            fn (): PurchaseDebitNote => app(PurchaseDebitNoteService::class)->allocate($note, $invoice->refresh(), '12.000000'),
        );

        $this->assertSame(PurchaseDebitNoteStatus::Posted, $note->status);
        $this->assertSame('0.000000', (string) $note->remaining_amount);
        $this->assertSame('49180.000000', (string) $invoice->refresh()->balance_due);
    }

    public function test_purchase_invoice_source_requires_exact_organization_scope(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $organizationUnitId = $this->createOrganizationUnit($tenantId, 'PUR-ORG-A');
        $otherOrganizationUnitId = $this->createOrganizationUnit($tenantId, 'PUR-ORG-B');
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $this->withTenantExecutionContext($tenantId, function () use ($grn, $organizationUnitId): void {
            $grn->organization_unit_id = $organizationUnitId;
            $grn->save();
        });

        try {
            $this->createSupplierInvoice(
                new CreatePurchaseInvoiceData(
                    tenantId: $tenantId,
                    invoiceDate: '2026-06-08',
                    organizationUnitId: $otherOrganizationUnitId,
                    sources: [
                        new PurchaseInvoiceSourceData(
                            'goods_receipt_note',
                            (int) $grn->getKey(),
                        ),
                    ],
                ),
            );
            $this->fail('Expected purchase invoice source organization validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The selected goods receipt is not available for this organization unit.',
                $exception->errors()['sources.0.source_id'][0] ?? null,
            );
        }
    }

    public function test_purchase_return_from_grn_line_posts_inventory_and_creates_debit_note(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $line = $grn->lines->first();

        try {
            $this->createPurchaseReturn(new CreatePurchaseReturnData(
                tenantId: $tenantId,
                returnDate: '2026-06-06',
                warehouseId: $warehouseId,
                lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $line->getKey(), '50.000000')],
            ));
            $this->fail('Expected over-return validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Returned quantity cannot exceed received remaining quantity.', $exception->getMessage());
        }

        $return = $this->createPurchaseReturn(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-06',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $line->getKey(), '8.000000')],
        ));

        $result = $this->postPurchaseReturn($return);

        $this->assertNotNull($result->debitNoteId);
        $note = $this->withTenantExecutionContext(
            $tenantId,
            fn (): PurchaseDebitNote => PurchaseDebitNote::query()->firstOrFail(),
        );
        $debitNoteCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => PurchaseDebitNote::query()->count(),
        );
        $returnMovementCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => InventoryMovement::query()->where('source_type', 'purchase_return')->count(),
        );
        $this->assertSame(1, $debitNoteCount);
        $this->assertSame(PurchaseDebitNoteStatus::Draft, $note->status);
        $this->assertSame('9840.000000', (string) $note->amount);
        $this->assertSame(1, $returnMovementCount);
    }

    public function test_purchase_return_rejects_mixed_grn_lines_and_ignores_spoofed_supplier(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grnOne, $grnTwo] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $otherSupplierId = $this->createSupplier($tenantId, 'SUP-SPOOF-'.Str::upper(Str::random(4)));

        try {
            $this->createPurchaseReturn(new CreatePurchaseReturnData(
                tenantId: $tenantId,
                returnDate: '2026-06-06',
                warehouseId: $warehouseId,
                lines: [
                    new PurchaseReturnLineData('goods_receipt_note_line', (int) $grnOne->lines->first()->getKey(), '1.000000'),
                    new PurchaseReturnLineData('goods_receipt_note_line', (int) $grnTwo->lines->first()->getKey(), '1.000000'),
                ],
            ));
            $this->fail('Expected mixed GRN return validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Purchase return lines must belong to the same goods receipt note.', $exception->getMessage());
        }

        $return = $this->createPurchaseReturn(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-06',
            warehouseId: $warehouseId,
            supplierType: 'supplier',
            supplierId: $otherSupplierId,
            sourceType: 'goods_receipt_note',
            sourceId: (int) $grnOne->getKey(),
            approvalRequired: true,
            affectsSupplierBalance: false,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $grnOne->lines->first()->getKey(), '1.000000')],
        ));

        $this->assertSame((int) $order->supplier_id, (int) $return->supplier_id);
        $this->assertFalse((bool) $return->approval_required);
        $this->assertTrue((bool) $return->affects_supplier_balance);
    }

    public function test_partial_return_prorates_line_discount_tax_and_charge(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = $this->createPurchaseOrder(new CreatePurchaseOrderData(
            tenantId: $tenantId,
            purchaseOrderDate: '2026-06-06',
            supplierType: 'supplier',
            supplierId: $supplierId,
            warehouseId: $warehouseId,
            lines: [new PurchaseOrderLineData(
                itemId: (int) $item->getKey(),
                orderedQuantity: '10.000000',
                unitPrice: '100.000000',
                uomId: $uomId,
                discountAmount: '10.000000',
                taxAmount: '18.000000',
                chargeAmount: '5.000000',
            )],
        ));
        $order = $this->approveOrder($order);
        $line = $order->lines->first();
        $grn = $this->createGoodsReceiptNote(new CreateGoodsReceiptNoteData(
            tenantId: $tenantId,
            receivedDate: '2026-06-06',
            warehouseId: $warehouseId,
            purchaseOrderId: (int) $order->getKey(),
            lines: [new GoodsReceiptNoteLineData((int) $item->getKey(), '10.000000', '10.000000', '100.000000', purchaseOrderLineId: (int) $line->getKey(), orderedQuantity: '10.000000')],
        ));
        $grn = $this->postGoodsReceiptNote($grn);
        $grn = $this->withTenantExecutionContext($tenantId, fn (): GoodsReceiptNote => $grn->load('lines'));

        $return = $this->createPurchaseReturn(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-07',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $grn->lines->first()->getKey(), '5.000000')],
        ));
        $return = $this->withTenantExecutionContext($tenantId, fn () => $return->load('lines'));
        $returnLine = $return->lines->first();

        $this->assertSame('5.000000', (string) $returnLine->discount_amount);
        $this->assertSame('9.000000', (string) $returnLine->tax_amount);
        $this->assertSame('2.500000', (string) $returnLine->charge_amount);
        $this->assertSame('506.500000', (string) $returnLine->line_total);
        $this->assertSame('506.500000', (string) $return->grand_total);
    }

    public function test_draft_purchase_return_blocks_grn_reversal(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $return = $this->createPurchaseReturn(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-08',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $grn->lines->first()->getKey(), '1.000000')],
        ));

        try {
            $this->reverseGoodsReceiptNote($grn);
            $this->fail('Expected draft purchase return to block GRN reversal.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Cannot reverse GRN while purchase returns are unresolved or impacting', $exception->getMessage());
            $this->assertStringContainsString((string) $return->return_number, $exception->getMessage());
        }

        $this->assertSame(GoodsReceiptNoteStatus::Posted, $grn->refresh()->status);
    }

    public function test_reversed_grn_blocks_existing_draft_return_posting(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $return = $this->createPurchaseReturn(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-08',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $grn->lines->first()->getKey(), '1.000000')],
        ));
        DB::table('goods_receipt_notes')->where('id', $grn->getKey())->update(['status' => GoodsReceiptNoteStatus::Reversed->value]);
        DB::table('goods_receipt_note_lines')->where('goods_receipt_note_id', $grn->getKey())->update(['status' => 'reversed']);

        try {
            $this->postPurchaseReturn($return);
            $this->fail('Expected reversed GRN to block purchase return posting.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Purchase return source goods receipt is no longer returnable.', $exception->getMessage());
        }

        $returnMovementCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => InventoryMovement::query()->where('source_type', 'purchase_return')->count(),
        );
        $debitNoteCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => PurchaseDebitNote::query()->count(),
        );
        $this->assertSame(0, $returnMovementCount);
        $this->assertSame(0, $debitNoteCount);
    }

    public function test_partial_return_final_residual_reconciles_source_amounts(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = $this->createPurchaseOrder(new CreatePurchaseOrderData(
            tenantId: $tenantId,
            purchaseOrderDate: '2026-06-06',
            supplierType: 'supplier',
            supplierId: $supplierId,
            warehouseId: $warehouseId,
            lines: [new PurchaseOrderLineData(
                itemId: (int) $item->getKey(),
                orderedQuantity: '3.000000',
                unitPrice: '10.000000',
                uomId: $uomId,
                discountAmount: '1.000000',
                taxAmount: '1.000000',
                chargeAmount: '1.000000',
            )],
            adjustments: [
                new PurchaseHeaderAdjustmentData('Freight', PurchaseAdjustmentType::Freight, PurchaseAdjustmentEffect::Increase, '1.000000'),
            ],
        ));
        $order = $this->approveOrder($order);
        $line = $order->lines->first();
        $grn = $this->createGoodsReceiptNote(new CreateGoodsReceiptNoteData(
            tenantId: $tenantId,
            receivedDate: '2026-06-06',
            warehouseId: $warehouseId,
            purchaseOrderId: (int) $order->getKey(),
            lines: [new GoodsReceiptNoteLineData((int) $item->getKey(), '3.000000', '3.000000', '10.000000', purchaseOrderLineId: (int) $line->getKey(), orderedQuantity: '3.000000')],
        ));
        $grn = $this->postGoodsReceiptNote($grn);
        $grn = $this->withTenantExecutionContext($tenantId, fn (): GoodsReceiptNote => $grn->load(['lines', 'adjustments']));
        $sourceLine = $grn->lines->first();

        for ($i = 0; $i < 3; $i++) {
            $return = $this->createPurchaseReturn(new CreatePurchaseReturnData(
                tenantId: $tenantId,
                returnDate: '2026-06-0'.(7 + $i),
                warehouseId: $warehouseId,
                lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $sourceLine->getKey(), '1.000000')],
            ));
            $this->postPurchaseReturn($return);
        }

        $returnLines = $this->withTenantExecutionContext(
            $tenantId,
            fn () => PurchaseReturnLine::query()
                ->where('source_line_type', 'goods_receipt_note_line')
                ->where('source_line_id', $sourceLine->getKey())
                ->orderBy('id')
                ->get(),
        );
        $math = app(\Modules\Core\Services\DecimalMath::class);

        $this->assertSame('30.000000', $math->sum($returnLines->pluck('base_amount')->map(fn ($value): string => (string) $value)->all()));
        $this->assertSame('1.000000', $math->sum($returnLines->pluck('discount_amount')->map(fn ($value): string => (string) $value)->all()));
        $this->assertSame('1.000000', $math->sum($returnLines->pluck('tax_amount')->map(fn ($value): string => (string) $value)->all()));
        $this->assertSame('1.000000', $math->sum($returnLines->pluck('charge_amount')->map(fn ($value): string => (string) $value)->all()));
        $this->assertSame('31.000000', $math->sum($returnLines->pluck('line_total')->map(fn ($value): string => (string) $value)->all()));
        $this->assertSame('0.333334', (string) $returnLines->last()->discount_amount);

        $adjustment = $grn->adjustments->first();
        $returnedAdjustment = $math->sum(DB::table('purchase_return_adjustment_allocations')
            ->where('purchase_header_adjustment_id', $adjustment->getKey())
            ->pluck('returned_amount')
            ->map(fn ($value): string => (string) $value)
            ->all());
        $this->assertSame('1.000000', $returnedAdjustment);
    }

    public function test_purchase_order_close_rejects_unresolved_returns(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $order = $this->withTenantExecutionContext($tenantId, fn (): PurchaseOrder => $order->refresh()->load('lines'));
        $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PurchaseOrderService::class)->applyInvoiced($order->lines->first(), '100.000000'),
        );
        $return = $this->createPurchaseReturn(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-08',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $grn->lines->first()->getKey(), '1.000000')],
        ));

        try {
            $this->withTenantExecutionContext(
                $tenantId,
                fn () => app(PurchaseOrderService::class)->close($order->refresh()),
            );
            $this->fail('Expected unresolved return to block purchase order close.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Purchase order cannot be closed while purchase returns are unresolved', $exception->getMessage());
            $this->assertStringContainsString((string) $return->return_number, $exception->getMessage());
        }
    }

    public function test_closed_purchase_order_rejects_later_grn_creation(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $order = $this->withTenantExecutionContext($tenantId, fn (): PurchaseOrder => $order->refresh()->load('lines'));
        $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PurchaseOrderService::class)->applyInvoiced($order->lines->first(), '100.000000'),
        );
        $closed = $this->withTenantExecutionContext(
            $tenantId,
            fn (): PurchaseOrder => app(PurchaseOrderService::class)->close($order->refresh()),
        );

        try {
            $this->createGoodsReceiptNote(new CreateGoodsReceiptNoteData(
                tenantId: $tenantId,
                receivedDate: '2026-06-09',
                warehouseId: $warehouseId,
                purchaseOrderId: (int) $closed->getKey(),
                lines: [new GoodsReceiptNoteLineData((int) $item->getKey(), '1.000000', '1.000000', '1000.000000', purchaseOrderLineId: (int) $order->lines->first()->getKey(), orderedQuantity: '100.000000')],
            ));
            $this->fail('Expected closed purchase order to reject later GRN creation.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Goods receipts can only be created from approved purchase orders.', $exception->getMessage());
        }
    }

    public function test_manual_return_debit_note_only_inventory_adjustment_only_and_payment_prepare_boundaries(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();

        $manualReturn = $this->createPurchaseReturn(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-06',
            warehouseId: $warehouseId,
            supplierType: 'supplier',
            supplierId: $supplierId,
            reason: 'Unknown old receipt',
            returnType: PurchaseReturnType::ManualSupplierReturn,
            approvalRequired: false,
            affectsSupplierBalance: false,
            costBasis: '10.000000',
            lines: [new PurchaseReturnLineData(null, null, '1.000000', itemId: (int) $item->getKey(), uomId: $uomId, costBasis: '10.000000', clientLineKey: 'manual-boundary-line')],
        ));
        $this->assertTrue((bool) $manualReturn->approval_required);
        $this->assertTrue((bool) $manualReturn->affects_supplier_balance);

        $note = $this->createDebitNote(new CreatePurchaseDebitNoteData(
            tenantId: $tenantId,
            debitNoteDate: '2026-06-06',
            amount: '20.000000',
            supplierType: 'supplier',
            supplierId: $supplierId,
            reason: 'Price dispute',
        ));
        $this->assertSame('20.000000', (string) $note->amount);
        $debitMovementCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => InventoryMovement::query()->where('source_type', 'purchase_debit_note')->count(),
        );
        $this->assertSame(0, $debitMovementCount);

        $adjustment = $this->createStockAdjustment(new StockAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: '2026-06-06',
            adjustmentType: InventoryAdjustmentType::OpeningBalance,
            warehouseId: $warehouseId,
            reason: 'Opening correction',
            lines: [new StockAdjustmentLineData((int) $item->getKey(), '0.000000', '1.000000', '1.000000', '10.000000')],
        ));
        $this->postStockAdjustment($adjustment);
        $inventoryMovementCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => InventoryMovement::query()->where('source_type', 'inventory_adjustment')->count(),
        );
        $debitNoteCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => PurchaseDebitNote::query()->count(),
        );
        $this->assertSame(1, $inventoryMovementCount);
        $this->assertSame(1, $debitNoteCount);

        $paymentMethodId = $this->paymentMethod($tenantId, 'MANUAL');
        $payment = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PurchasePaymentIntegrationService::class)->prepareSupplierPayment(
                tenantId: $tenantId,
                paymentDate: '2026-06-06',
                amount: '20.000000',
                supplierType: 'supplier',
                supplierId: $supplierId,
                lines: [new PaymentLineData('20.000000', $paymentMethodId, 'PAY-MANUAL')],
            ),
        );
        $this->assertCount(0, $payment->allocations);
        $paymentCount = $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => Payment::query()->count(),
        );
        $this->assertSame(0, $paymentCount);
    }

    public function test_tenant_isolation_is_enforced(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $otherTenant = $this->createTenant('OTHER');
        $otherWarehouse = $this->createWarehouse($otherTenant, 'WH-OTHER');

        try {
            $this->createPurchaseOrder(new CreatePurchaseOrderData(
                tenantId: $tenantId,
                purchaseOrderDate: '2026-06-06',
                supplierType: 'supplier',
                supplierId: $supplierId,
                warehouseId: $otherWarehouse,
                lines: [new PurchaseOrderLineData((int) $item->getKey(), '1.000000', '1.000000', uomId: $uomId)],
            ));
            $this->fail('Expected purchase warehouse tenant validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame('The selected warehouse is not available.', $exception->errors()['warehouse_id'][0] ?? null);
        }
    }

    public function test_purchase_authorization_ignores_soft_deleted_and_cross_tenant_roles(): void
    {
        $tenantId = $this->createTenant('AUTHA');
        $otherTenantId = $this->createTenant('AUTHB');
        $userId = (int) \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Purchase',
            'last_name' => 'Auth',
            'email' => 'purchase-auth-'.Str::lower(Str::random(6)).'@example.test',
            'password' => 'secret',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionId = $this->insertPurchasePermission($tenantId, PurchaseAuthorizationService::ORDERS_VIEW);
        $deletedRoleId = $this->insertRole($tenantId, 'Deleted Purchase Role', deleted: true);
        DB::table('role_permissions')->insert([
            'tenant_id' => $tenantId,
            'role_id' => $deletedRoleId,
            'permission_id' => $permissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $deletedRoleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherUserId = (int) \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $otherTenantId,
            'first_name' => 'Other',
            'last_name' => 'Auth',
            'email' => 'purchase-other-auth-'.Str::lower(Str::random(6)).'@example.test',
            'password' => 'secret',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherPermissionId = $this->insertPurchasePermission($otherTenantId, PurchaseAuthorizationService::ORDERS_VIEW);
        $otherRoleId = $this->insertRole($otherTenantId, 'Other Tenant Purchase Role');
        DB::table('role_permissions')->insert([
            'tenant_id' => $otherTenantId,
            'role_id' => $otherRoleId,
            'permission_id' => $otherPermissionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_roles')->insert([
            'tenant_id' => $otherTenantId,
            'user_id' => $otherUserId,
            'role_id' => $otherRoleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $authorization = app(PurchaseAuthorizationService::class);
        $this->assertFalse($authorization->can($userId, $tenantId, PurchaseAuthorizationService::ORDERS_VIEW));
        $this->assertFalse($authorization->can($userId, $otherTenantId, PurchaseAuthorizationService::ORDERS_VIEW));
    }

    public function test_payment_and_finance_preparation_dtos_are_created_without_persistence(): void
    {
        [$tenantId, , , $supplierId] = $this->purchaseContext();
        $paymentMethodId = $this->paymentMethod($tenantId, 'DTO');
        $payment = $this->withTenantExecutionContext(
            $tenantId,
            fn () => app(PurchasePaymentIntegrationService::class)->prepareSupplierPayment(
                tenantId: $tenantId,
                paymentDate: '2026-06-06',
                amount: '100.000000',
                supplierType: 'supplier',
                supplierId: $supplierId,
                lines: [new PaymentLineData('100.000000', $paymentMethodId, 'PAY-DTO')],
            ),
        );
        $journal = app(PurchaseFinancePreparationService::class)->prepareJournal(
            tenantId: $tenantId,
            journalDate: '2026-06-06',
            sourceType: 'purchase_order',
            sourceId: 1,
            lines: [new FinancePostingLine(accountName: 'Purchase Expense', debit: '100.000000', profileKey: 'expense')],
        );

        $this->assertSame(PaymentType::SupplierPayment, $payment->paymentType);
        $this->assertSame(PaymentDirection::Outbound, $payment->direction);
        $this->assertSame('purchase_order', $journal->source->sourceType);
        $this->assertSame('purchase', $journal->source->sourceModule);
    }

    private function fakeFinancePosting(): void
    {
        $this->app->instance(FinancePostingInterface::class, new class implements FinancePostingInterface
        {
            public function createDraftJournal(PostingContext $request): PostingResultData
            {
                return $this->result('draft');
            }

            public function validatePosting(PostingContext $request): void {}

            public function post(PostingContext $request, ?int $postedBy = null): PostingResultData
            {
                return $this->result('posted');
            }

            public function postJournal(int $journalId, ?int $postedBy = null): PostingResultData
            {
                return $this->result('posted');
            }

            public function reverseJournal(int $journalId, string $reversalDate, ?int $reversedBy = null, ?string $reason = null): PostingResultData
            {
                return $this->result('reversed');
            }

            private function result(string $status): PostingResultData
            {
                return new PostingResultData(1, 'JRN-TEST', $status, '0.000000', '0.000000');
            }
        });
    }

    private function createPurchaseOrder(CreatePurchaseOrderData $data): PurchaseOrder
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): PurchaseOrder => app(PurchaseOrderService::class)->create($data),
        );
    }

    private function createGoodsReceiptNote(CreateGoodsReceiptNoteData $data): GoodsReceiptNote
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): GoodsReceiptNote => app(GoodsReceiptNoteService::class)->create($data),
        );
    }

    private function postGoodsReceiptNote(GoodsReceiptNote $grn): GoodsReceiptNote
    {
        return $this->withTenantExecutionContext(
            (int) $grn->tenant_id,
            fn (): GoodsReceiptNote => app(GoodsReceiptNoteService::class)->post($grn),
        );
    }

    private function reverseGoodsReceiptNote(GoodsReceiptNote $grn): GoodsReceiptNote
    {
        return $this->withTenantExecutionContext(
            (int) $grn->tenant_id,
            fn (): GoodsReceiptNote => app(GoodsReceiptNoteService::class)->reverse($grn),
        );
    }

    private function createSupplierInvoice(CreatePurchaseInvoiceData $data): \Modules\Invoice\Models\Invoice
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): \Modules\Invoice\Models\Invoice => app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice($data),
        );
    }

    private function previewSupplierInvoice(CreatePurchaseInvoiceData $data): mixed
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): mixed => app(PurchaseInvoiceIntegrationService::class)->previewSupplierInvoice($data),
        );
    }

    private function createPurchaseReturn(CreatePurchaseReturnData $data): mixed
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): mixed => app(PurchaseReturnService::class)->create($data),
        );
    }

    private function postPurchaseReturn(mixed $return): mixed
    {
        return $this->withTenantExecutionContext(
            (int) $return->tenant_id,
            fn (): mixed => app(PurchaseReturnService::class)->post($return),
        );
    }

    private function approvePurchaseReturn(mixed $return): mixed
    {
        return $this->withTenantExecutionContext(
            (int) $return->tenant_id,
            fn (): mixed => app(PurchaseReturnService::class)->approve($return),
        );
    }

    private function createDebitNote(CreatePurchaseDebitNoteData $data): PurchaseDebitNote
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): PurchaseDebitNote => app(PurchaseDebitNoteService::class)->create($data),
        );
    }

    private function createPayment(CreatePaymentData $data): Payment
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): Payment => app(PaymentCreationService::class)->create($data),
        );
    }

    private function allocatePayment(Payment $payment, array $allocations, int $expectedVersion): Payment
    {
        return $this->withTenantExecutionContext(
            (int) $payment->tenant_id,
            fn (): Payment => app(PaymentAllocationService::class)->allocate($payment, $allocations, $expectedVersion),
        );
    }

    private function createStockAdjustment(StockAdjustmentData $data): mixed
    {
        return $this->withTenantExecutionContext(
            $data->tenantId,
            fn (): mixed => app(StockAdjustmentService::class)->create($data),
        );
    }

    private function postStockAdjustment(mixed $adjustment): mixed
    {
        return $this->withTenantExecutionContext(
            (int) $adjustment->tenant_id,
            fn (): mixed => app(StockAdjustmentService::class)->post($adjustment),
        );
    }

    private function createAdjustedOrder(int $tenantId, int $warehouseId, Item $item): PurchaseOrder
    {
        $taxGroupId = $this->taxGroup($tenantId);

        return $this->createPurchaseOrder(new CreatePurchaseOrderData(
            tenantId: $tenantId,
            purchaseOrderDate: '2026-06-06',
            supplierType: 'supplier',
            supplierId: $this->supplierId($tenantId),
            warehouseId: $warehouseId,
            lines: [
                new PurchaseOrderLineData(
                    (int) $item->getKey(),
                    '100.000000',
                    '1000.000000',
                    uomId: (int) $item->base_uom_id,
                    taxCalculationType: PurchaseAdjustmentCalculationType::Percentage,
                    taxRate: '18.000000',
                    taxGroupId: $taxGroupId,
                ),
            ],
            adjustments: [
                new PurchaseHeaderAdjustmentData('Discount', PurchaseAdjustmentType::Discount, PurchaseAdjustmentEffect::Decrease, '5000.000000'),
                new PurchaseHeaderAdjustmentData('Freight', PurchaseAdjustmentType::Freight, PurchaseAdjustmentEffect::Increase, '10000.000000'),
            ],
        ));
    }

    /**
     * @return array{GoodsReceiptNote, GoodsReceiptNote}
     */
    private function receiveOrderInTwoParts(PurchaseOrder $order, int $warehouseId, Item $item): array
    {
        return $this->withTenantExecutionContext((int) $order->tenant_id, function () use ($order, $warehouseId, $item): array {
            $order = $this->approveOrder($order);
            $line = $order->lines->first();
            $first = app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
                tenantId: (int) $order->tenant_id,
                receivedDate: '2026-06-06',
                warehouseId: $warehouseId,
                purchaseOrderId: (int) $order->getKey(),
                lines: [
                    new GoodsReceiptNoteLineData((int) $item->getKey(), '40.000000', '40.000000', '1000.000000', purchaseOrderLineId: (int) $line->getKey(), orderedQuantity: '100.000000'),
                ],
            ));
            app(GoodsReceiptNoteService::class)->post($first);

            $second = app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
                tenantId: (int) $order->tenant_id,
                receivedDate: '2026-06-07',
                warehouseId: $warehouseId,
                purchaseOrderId: (int) $order->getKey(),
                lines: [
                    new GoodsReceiptNoteLineData((int) $item->getKey(), '60.000000', '60.000000', '1000.000000', purchaseOrderLineId: (int) $line->getKey(), orderedQuantity: '100.000000'),
                ],
            ));
            app(GoodsReceiptNoteService::class)->post($second);

            return [$first->refresh()->load(['lines', 'adjustments']), $second->refresh()->load(['lines', 'adjustments'])];
        });
    }

    private function approveOrder(PurchaseOrder $order): PurchaseOrder
    {
        return $this->withTenantExecutionContext((int) $order->tenant_id, function () use ($order): PurchaseOrder {
            if ($order->status === PurchaseOrderStatus::Approved) {
                return $order->refresh()->load('lines');
            }

            $orders = app(PurchaseOrderService::class);
            if ($order->status === PurchaseOrderStatus::Draft) {
                $order = $orders->submit($order);
            }

            if ($order->status === PurchaseOrderStatus::PendingApproval) {
                $order = $orders->approve($order);
            }

            return $order->refresh()->load('lines');
        });
    }

    private function purchaseContext(): array
    {
        $tenantId = $this->createTenant();
        $uomId = $this->createUom($tenantId, 'PCS-'.Str::upper(Str::random(4)));
        $supplierId = $this->createSupplier($tenantId, 'SUP-'.Str::upper(Str::random(4)));
        $warehouseId = $this->createWarehouse($tenantId, 'WH-'.Str::upper(Str::random(4)));
        $item = $this->createItem($tenantId, 'ITEM-'.Str::upper(Str::random(4)), uomId: $uomId);

        return [$tenantId, $warehouseId, $item, $supplierId, $uomId];
    }

    private function postInvoice(\Modules\Invoice\Models\Invoice $invoice): \Modules\Invoice\Models\Invoice
    {
        return $this->withTenantExecutionContext((int) $invoice->tenant_id, function () use ($invoice): \Modules\Invoice\Models\Invoice {
            $statuses = app(InvoiceStatusService::class);
            $invoice = $statuses->transition($invoice, InvoiceStatus::Approved);

            return $statuses->transition($invoice, InvoiceStatus::Posted);
        });
    }

    private function paymentMethod(int $tenantId, string $suffix): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId,
            'scope_key' => 'tenant:'.$tenantId,
            'code' => 'CASH-'.$suffix.'-'.Str::upper(Str::random(4)),
            'name' => 'Cash',
            'method_type' => 'cash',
            'direction_allowed' => 'outbound',
            'requires_reference' => false,
            'requires_instrument_details' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function taxGroup(int $tenantId): int
    {
        $suffix = Str::upper(Str::random(5));
        $taxId = (int) DB::table('taxes')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'VAT-18-'.$suffix,
            'name' => 'VAT 18%',
            'tax_type' => 'vat',
            'calculation_method' => 'exclusive',
            'is_withholding' => false,
            'recoverable' => true,
            'payable' => true,
            'receivable' => false,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tax_rates')->insert([
            'tenant_id' => $tenantId,
            'tax_id' => $taxId,
            'rate' => '18.000000',
            'effective_from' => '2026-01-01',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $groupId = (int) DB::table('tax_groups')->insertGetId([
            'tenant_id' => $tenantId,
            'code' => 'PURCHASE-VAT-'.$suffix,
            'name' => 'Purchase VAT 18%',
            'is_default' => false,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tax_group_lines')->insert([
            'tenant_id' => $tenantId,
            'tax_group_id' => $groupId,
            'tax_id' => $taxId,
            'sequence' => 1,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $groupId;
    }

    private function createItem(int $tenantId, string $code, ItemType $type = ItemType::Stock, bool $stockable = true, ?int $uomId = null): Item
    {
        return $this->withTenantExecutionContext(
            $tenantId,
            fn (): Item => app(ItemCreationService::class)->create(new CreateItemData(
                tenantId: $tenantId,
                code: $code,
                name: 'Purchase '.$code,
                itemType: $type,
                trackingType: TrackingType::None,
                costingMethod: $stockable ? CostingMethod::Fifo : CostingMethod::None,
                baseUomId: $uomId,
                isStockable: $stockable,
            )),
        );
    }

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-PUR-'.$suffix,
            'name' => 'Purchase Tenant '.$suffix,
            'slug' => 'purchase-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function createOrganizationUnit(int $tenantId, string $name): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => $name,
            'code' => $name,
            'depth' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWarehouse(int $tenantId, string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Warehouse '.$code,
            'code' => $code,
            'type' => 'standard',
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUom(int $tenantId, string $code, bool $isBase = true): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'code' => $code,
            'name' => 'Unit '.$code,
            'symbol' => 'pcs',
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => $isBase,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSupplier(int $tenantId, string $code): int
    {
        return (int) DB::table('suppliers')->insertGetId([
            'tenant_id' => $tenantId,
            'supplier_number' => $code,
            'code' => $code,
            'name' => 'Supplier '.$code,
            'display_name' => 'Supplier '.$code,
            'supplier_type' => 'local',
            'status' => 'active',
            'is_credit_allowed' => true,
            'is_advance_allowed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPurchasePermission(int $tenantId, string $name): int
    {
        return (int) DB::table('permissions')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'guard_name' => 'auth-api',
            'module' => 'Purchase',
            'description' => PurchaseAuthorizationService::descriptions()[$name] ?? $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertRole(int $tenantId, string $name, bool $deleted = false): int
    {
        return (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => $name,
            'guard_name' => 'auth-api',
            'description' => $name,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => $deleted ? now() : null,
        ]);
    }

    private function supplierId(int $tenantId): int
    {
        return (int) DB::table('suppliers')->where('tenant_id', $tenantId)->value('id');
    }
}
