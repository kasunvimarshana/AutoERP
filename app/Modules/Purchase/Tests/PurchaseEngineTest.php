<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Finance\DTOs\FinancePostingLine;
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
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
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

        $order = app(PurchaseOrderService::class)->create(new CreatePurchaseOrderData(
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
        $order = app(PurchaseOrderService::class)->create(new CreatePurchaseOrderData(
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

        $grn = app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
            tenantId: $tenantId,
            receivedDate: '2026-06-06',
            warehouseId: $warehouseId,
            purchaseOrderId: (int) $order->getKey(),
            lines: [
                new GoodsReceiptNoteLineData((int) $item->getKey(), '4.000000', '4.000000', '100.000000', purchaseOrderLineId: (int) $order->lines[0]->getKey(), orderedQuantity: '10.000000'),
                new GoodsReceiptNoteLineData((int) $service->getKey(), '1.000000', '1.000000', '50.000000', purchaseOrderLineId: (int) $order->lines[1]->getKey(), orderedQuantity: '1.000000'),
            ],
        ));

        $posted = app(GoodsReceiptNoteService::class)->post($grn);

        $this->assertSame(GoodsReceiptNoteStatus::Posted, $posted->status);
        $this->assertCount(1, InventoryMovement::query()->where('source_type', 'goods_receipt_note')->get());
        $availability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId));
        $this->assertSame('4.000000', $availability->quantityOnHand);
        $this->assertSame(PurchaseOrderStatus::Approved, $order->refresh()->status);
        $this->assertSame('4.000000', (string) $order->lines()->where('item_id', $item->getKey())->firstOrFail()->received_quantity);
    }

    public function test_many_grns_can_create_one_supplier_invoice_with_header_adjustments(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grnOne, $grnTwo] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);

        $invoice = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(new CreatePurchaseInvoiceData(
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
        $this->assertCount(2, PurchaseInvoiceLink::query()->where('invoice_id', $invoice->getKey())->get());
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
            app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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

        $invoiceOne = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: $tenantId,
            invoiceDate: '2026-06-06',
            sources: [new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grn->getKey(), [$lineId => '4.000000'])],
        ));

        $invoiceTwo = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: $tenantId,
            invoiceDate: '2026-06-07',
            sources: [new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grn->getKey())],
        ));

        $this->assertSame('4920.000000', (string) $invoiceOne->grand_total);
        $this->assertSame('44280.000000', (string) $invoiceTwo->grand_total);
        $this->assertSame(GoodsReceiptNoteStatus::Posted, $grn->refresh()->status);
        $this->assertSame('40.000000', (string) $grn->lines()->firstOrFail()->invoiced_quantity);
    }

    public function test_example_40_percent_grn_invoice_allocates_adjustments(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);

        $invoice = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: $tenantId,
            invoiceDate: '2026-06-06',
            sources: [new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grn->getKey())],
        ));

        $this->assertSame('40000.000000', (string) $invoice->subtotal);
        $this->assertSame('2000.000000', (string) $invoice->discount_total);
        $this->assertSame('7200.000000', (string) $invoice->tax_total);
        $this->assertSame('4000.000000', (string) $invoice->charge_total);
        $this->assertSame('49200.000000', (string) $invoice->grand_total);
        $this->assertSame(2, PurchaseHeaderAdjustment::query()->where('source_type', 'goods_receipt_note')->where('source_id', $grn->getKey())->count());
    }

    public function test_partial_receipt_partial_invoice_and_supplier_payment_preparation_workflow(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();

        $invoice = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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

        $payment = app(PurchasePaymentIntegrationService::class)->prepareSupplierPayment(
            tenantId: $tenantId,
            paymentDate: '2026-06-09',
            amount: (string) $invoice->grand_total,
            supplierType: 'supplier',
            supplierId: $supplierId,
            allocations: [
                new PaymentAllocationData(
                    (int) $invoice->getKey(),
                    (string) $invoice->grand_total,
                    '2026-06-09',
                ),
            ],
        );

        $this->assertSame(GoodsReceiptNoteStatus::Posted, $grn->refresh()->status);
        $this->assertSame(PurchaseOrderStatus::Approved, $order->refresh()->status);
        $this->assertSame('20.000000', (string) $grn->lines()->firstOrFail()->invoiced_quantity);
        $this->assertSame(PaymentType::SupplierPayment, $payment->paymentType);
        $this->assertSame(PaymentDirection::Outbound, $payment->direction);
        $this->assertSame((int) $invoice->getKey(), $payment->allocations[0]->invoiceId);
        $this->assertSame((string) $invoice->grand_total, $payment->allocations[0]->allocatedAmount);
    }

    public function test_supplier_payment_creation_persists_payment_and_allocation(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();

        $invoice = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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

        $payment = app(PurchasePaymentIntegrationService::class)->createSupplierPayment(
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
        );

        $this->assertSame(1, Payment::query()->count());
        $this->assertSame((string) $invoice->grand_total, (string) $payment->total_amount);
        $this->assertSame('0.000000', (string) $payment->allocated_amount);
        $this->assertSame((string) $invoice->grand_total, (string) $invoice->refresh()->balance_due);
        $this->assertSame('pending', (string) $payment->allocations->firstOrFail()->status->value);
    }

    public function test_supplier_payment_preview_is_non_persistent_and_preserves_invoice_balance(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();
        $invoice = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(new CreatePurchaseInvoiceData(
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
            $preview = app(PurchasePaymentIntegrationService::class)->previewSupplierPayment(
                tenantId: $tenantId,
                paymentDate: '2026-06-09',
                amount: (string) $invoice->grand_total,
                supplierType: 'supplier',
                supplierId: $supplierId,
                lines: [new PaymentLineData((string) $invoice->grand_total, $paymentMethodId, 'PAY-PREVIEW')],
                allocations: [new PaymentAllocationData((int) $invoice->getKey(), (string) $invoice->grand_total, '2026-06-09')],
            );

            $this->assertSame((string) $invoice->grand_total, $preview->allocationTotal);
            $this->assertSame('0.000000', $preview->unappliedAmount);
        }

        $this->assertSame(0, Payment::query()->count());
        $this->assertSame($balanceBefore, (string) $invoice->refresh()->balance_due);
    }

    public function test_purchase_return_approval_matrix_respects_approval_required(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();
        $returns = app(PurchaseReturnService::class);

        $noApproval = $returns->create(new CreatePurchaseReturnData(
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
            $returns->approve($noApproval);
            $this->fail('Expected non-approval return approval to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Purchase return does not require approval.', $exception->getMessage());
        }

        $postedWithoutApproval = $returns->post($noApproval);
        $this->assertSame(PurchaseReturnStatus::Posted->value, $postedWithoutApproval->status);

        $approvalRequired = $returns->create(new CreatePurchaseReturnData(
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
            $returns->post($approvalRequired);
            $this->fail('Expected approval-required return posting to fail before approval.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Purchase return must be approved before posting.', $exception->getMessage());
        }

        $approved = $returns->approve($approvalRequired);
        $this->assertSame(PurchaseReturnStatus::Approved, $approved->status);
        $this->assertTrue((bool) $approved->approval_required);
    }

    public function test_sequential_double_invoicing_is_prevented(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $lineId = (int) $grn->lines->first()->getKey();

        app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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

        app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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
        $grn = app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
            tenantId: $tenantId,
            receivedDate: '2026-06-06',
            warehouseId: $warehouseId,
            purchaseOrderId: (int) $order->getKey(),
            lines: [new GoodsReceiptNoteLineData((int) $item->getKey(), '40.000000', '40.000000', '1000.000000', purchaseOrderLineId: $poLineId, orderedQuantity: '100.000000')],
        ));
        $grn = app(GoodsReceiptNoteService::class)->post($grn)->load('lines');
        $grnLineId = (int) $grn->lines->first()->getKey();

        try {
            app(PurchaseInvoiceIntegrationService::class)->previewSupplierInvoice(new CreatePurchaseInvoiceData(
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
            app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
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
        $advance = app(PaymentCreationService::class)->create(new CreatePaymentData(
            tenantId: $tenantId,
            paymentType: PaymentType::Advance,
            direction: PaymentDirection::Outbound,
            paymentDate: '2026-06-05',
            partyType: 'supplier',
            partyId: $supplierId,
            status: PaymentStatus::Posted,
            lines: [new PaymentLineData(
                amount: '60000.000000',
                paymentMethodId: $this->paymentMethod($tenantId, 'ADVANCE'),
            )],
        ));

        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $invoice = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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

        $advance = app(PaymentAllocationService::class)->allocate($advance, [
            new PaymentAllocationData(
                invoiceId: (int) $invoice->getKey(),
                allocatedAmount: (string) $invoice->grand_total,
                allocationDate: '2026-06-09',
            ),
        ]);

        $this->assertSame(PaymentStatus::Posted, $advance->status);
        $this->assertSame('49200.000000', (string) $advance->allocated_amount);
        $this->assertSame('10800.000000', (string) $advance->unapplied_amount);
        $this->assertSame('0.000000', (string) $invoice->refresh()->balance->remaining_amount);
    }

    public function test_cancelled_purchase_invoice_restores_grn_and_order_invoiceable_quantity(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grn] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $invoice = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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

        app(InvoiceStatusService::class)->transition($invoice, InvoiceStatus::Cancelled);

        $grn = $grn->refresh()->load('lines');
        $order = $order->refresh()->load('lines');
        $this->assertSame(GoodsReceiptNoteStatus::Posted, $grn->status);
        $this->assertSame('0.000000', (string) $grn->lines->first()->invoiced_quantity);
        $this->assertSame('40.000000', (string) $grn->lines->first()->remaining_quantity);
        $this->assertSame('0.000000', (string) $order->lines->first()->invoiced_quantity);
        $this->assertSame('100.000000', (string) $order->lines->first()->remaining_invoiceable_quantity);
        $this->assertSame(
            'cancelled',
            PurchaseInvoiceLink::query()->where('invoice_id', $invoice->getKey())->value('status'),
        );

        $replacement = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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
        $invoice = app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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
        $statuses = app(InvoiceStatusService::class);
        $invoice = $statuses->transition($invoice, InvoiceStatus::Approved);
        $invoice = $statuses->transition($invoice, InvoiceStatus::Posted);
        $this->assertSame('posted', DB::table('invoices')->where('id', $invoice->getKey())->value('status'));

        $debitNotes = app(PurchaseDebitNoteService::class);
        $note = $debitNotes->create(new CreatePurchaseDebitNoteData(
            tenantId: $tenantId,
            debitNoteDate: '2026-06-09',
            amount: '20.000000',
            supplierType: 'supplier',
            supplierId: $supplierId,
            sourceType: 'price_dispute',
            reason: 'Price dispute',
        ));
        $note = $debitNotes->approve($note);
        $note = $debitNotes->post($note);
        $note = $debitNotes->allocate($note, $invoice, '8.000000');

        $this->assertSame(PurchaseDebitNoteStatus::Posted, $note->status);
        $this->assertSame('12.000000', (string) $note->remaining_amount);
        $this->assertSame('49192.000000', (string) $invoice->refresh()->balance_due);

        $note = $debitNotes->allocate($note, $invoice->refresh(), '12.000000');

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
        $grn->organization_unit_id = $organizationUnitId;
        $grn->save();

        try {
            app(PurchaseInvoiceIntegrationService::class)->createSupplierInvoice(
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
            app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
                tenantId: $tenantId,
                returnDate: '2026-06-06',
                warehouseId: $warehouseId,
                lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $line->getKey(), '50.000000')],
            ));
            $this->fail('Expected over-return validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Returned quantity cannot exceed received remaining quantity.', $exception->getMessage());
        }

        $return = app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-06',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $line->getKey(), '8.000000')],
        ));

        $result = app(PurchaseReturnService::class)->post($return);

        $this->assertNotNull($result->debitNoteId);
        $this->assertSame(1, PurchaseDebitNote::query()->count());
        $note = PurchaseDebitNote::query()->firstOrFail();
        $this->assertSame(PurchaseDebitNoteStatus::Draft, $note->status);
        $this->assertSame('9840.000000', (string) $note->amount);
        $this->assertSame(1, InventoryMovement::query()->where('source_type', 'purchase_return')->count());
    }

    public function test_purchase_return_rejects_mixed_grn_lines_and_ignores_spoofed_supplier(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $order = $this->createAdjustedOrder($tenantId, $warehouseId, $item);
        [$grnOne, $grnTwo] = $this->receiveOrderInTwoParts($order, $warehouseId, $item);
        $otherSupplierId = $this->createSupplier($tenantId, 'SUP-SPOOF-'.Str::upper(Str::random(4)));

        try {
            app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
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

        $return = app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
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
        $order = app(PurchaseOrderService::class)->create(new CreatePurchaseOrderData(
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
        $grn = app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
            tenantId: $tenantId,
            receivedDate: '2026-06-06',
            warehouseId: $warehouseId,
            purchaseOrderId: (int) $order->getKey(),
            lines: [new GoodsReceiptNoteLineData((int) $item->getKey(), '10.000000', '10.000000', '100.000000', purchaseOrderLineId: (int) $line->getKey(), orderedQuantity: '10.000000')],
        ));
        $grn = app(GoodsReceiptNoteService::class)->post($grn)->load('lines');

        $return = app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-07',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $grn->lines->first()->getKey(), '5.000000')],
        ))->load('lines');
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
        $return = app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-08',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $grn->lines->first()->getKey(), '1.000000')],
        ));

        try {
            app(GoodsReceiptNoteService::class)->reverse($grn);
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
        $return = app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-08',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $grn->lines->first()->getKey(), '1.000000')],
        ));
        DB::table('goods_receipt_notes')->where('id', $grn->getKey())->update(['status' => GoodsReceiptNoteStatus::Reversed->value]);
        DB::table('goods_receipt_note_lines')->where('goods_receipt_note_id', $grn->getKey())->update(['status' => 'reversed']);

        try {
            app(PurchaseReturnService::class)->post($return);
            $this->fail('Expected reversed GRN to block purchase return posting.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Purchase return source goods receipt is no longer returnable.', $exception->getMessage());
        }

        $this->assertSame(0, InventoryMovement::query()->where('source_type', 'purchase_return')->count());
        $this->assertSame(0, PurchaseDebitNote::query()->count());
    }

    public function test_partial_return_final_residual_reconciles_source_amounts(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $order = app(PurchaseOrderService::class)->create(new CreatePurchaseOrderData(
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
        $grn = app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
            tenantId: $tenantId,
            receivedDate: '2026-06-06',
            warehouseId: $warehouseId,
            purchaseOrderId: (int) $order->getKey(),
            lines: [new GoodsReceiptNoteLineData((int) $item->getKey(), '3.000000', '3.000000', '10.000000', purchaseOrderLineId: (int) $line->getKey(), orderedQuantity: '3.000000')],
        ));
        $grn = app(GoodsReceiptNoteService::class)->post($grn)->load(['lines', 'adjustments']);
        $sourceLine = $grn->lines->first();

        for ($i = 0; $i < 3; $i++) {
            $return = app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
                tenantId: $tenantId,
                returnDate: '2026-06-0'.(7 + $i),
                warehouseId: $warehouseId,
                lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $sourceLine->getKey(), '1.000000')],
            ));
            app(PurchaseReturnService::class)->post($return);
        }

        $returnLines = PurchaseReturnLine::query()
            ->where('source_line_type', 'goods_receipt_note_line')
            ->where('source_line_id', $sourceLine->getKey())
            ->orderBy('id')
            ->get();
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
        $order = $order->refresh()->load('lines');
        app(PurchaseOrderService::class)->applyInvoiced($order->lines->first(), '100.000000');
        $return = app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
            tenantId: $tenantId,
            returnDate: '2026-06-08',
            warehouseId: $warehouseId,
            lines: [new PurchaseReturnLineData('goods_receipt_note_line', (int) $grn->lines->first()->getKey(), '1.000000')],
        ));

        try {
            app(PurchaseOrderService::class)->close($order->refresh());
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
        $order = $order->refresh()->load('lines');
        app(PurchaseOrderService::class)->applyInvoiced($order->lines->first(), '100.000000');
        $closed = app(PurchaseOrderService::class)->close($order->refresh());

        try {
            app(GoodsReceiptNoteService::class)->create(new CreateGoodsReceiptNoteData(
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

        $manualReturn = app(PurchaseReturnService::class)->create(new CreatePurchaseReturnData(
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

        $note = app(PurchaseDebitNoteService::class)->create(new CreatePurchaseDebitNoteData(
            tenantId: $tenantId,
            debitNoteDate: '2026-06-06',
            amount: '20.000000',
            supplierType: 'supplier',
            supplierId: $supplierId,
            sourceType: 'price_dispute',
            reason: 'Price dispute',
        ));
        $this->assertSame('20.000000', (string) $note->amount);
        $this->assertSame(0, InventoryMovement::query()->where('source_type', 'purchase_debit_note')->count());

        $adjustment = app(StockAdjustmentService::class)->create(new StockAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: '2026-06-06',
            adjustmentType: InventoryAdjustmentType::OpeningBalance,
            warehouseId: $warehouseId,
            reason: 'Opening correction',
            lines: [new StockAdjustmentLineData((int) $item->getKey(), '0.000000', '1.000000', '1.000000', '10.000000')],
        ));
        app(StockAdjustmentService::class)->post($adjustment);
        $this->assertSame(1, InventoryMovement::query()->where('source_type', 'inventory_adjustment')->count());
        $this->assertSame(1, PurchaseDebitNote::query()->count());

        $payment = app(PurchasePaymentIntegrationService::class)->prepareSupplierPayment(
            tenantId: $tenantId,
            paymentDate: '2026-06-06',
            amount: '20.000000',
            supplierType: 'supplier',
            supplierId: $supplierId,
            allocations: [new PaymentAllocationData(1, '20.000000', '2026-06-06')],
        );
        $this->assertCount(1, $payment->allocations);
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_tenant_isolation_is_enforced(): void
    {
        [$tenantId, $warehouseId, $item, $supplierId, $uomId] = $this->purchaseContext();
        $otherTenant = $this->createTenant('OTHER');
        $otherWarehouse = $this->createWarehouse($otherTenant, 'WH-OTHER');

        try {
            app(PurchaseOrderService::class)->create(new CreatePurchaseOrderData(
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
        $userId = (int) DB::table('users')->insertGetId([
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
            'user_id' => $userId,
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
        [$tenantId] = $this->purchaseContext();
        $payment = app(PurchasePaymentIntegrationService::class)->prepareSupplierPayment($tenantId, '2026-06-06', '100.000000');
        $journal = app(PurchaseFinancePreparationService::class)->prepareJournal(
            tenantId: $tenantId,
            journalDate: '2026-06-06',
            sourceType: 'purchase_order',
            sourceId: 1,
            lines: [new FinancePostingLine(accountCode: '5100', accountName: 'Purchase Expense', debit: '100.000000')],
        );

        $this->assertSame(PaymentType::SupplierPayment, $payment->paymentType);
        $this->assertSame(PaymentDirection::Outbound, $payment->direction);
        $this->assertSame('purchase_order', $journal->source->sourceType);
        $this->assertSame('purchase', $journal->source->sourceModule);
    }

    private function createAdjustedOrder(int $tenantId, int $warehouseId, Item $item): PurchaseOrder
    {
        $taxGroupId = $this->taxGroup($tenantId);

        return app(PurchaseOrderService::class)->create(new CreatePurchaseOrderData(
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
    }

    private function approveOrder(PurchaseOrder $order): PurchaseOrder
    {
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
        $statuses = app(InvoiceStatusService::class);
        $invoice = $statuses->transition($invoice, InvoiceStatus::Approved);

        return $statuses->transition($invoice, InvoiceStatus::Posted);
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
            'requires_bank_account' => false,
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
        return app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            code: $code,
            name: 'Purchase '.$code,
            itemType: $type,
            trackingType: TrackingType::None,
            costingMethod: $stockable ? CostingMethod::Fifo : CostingMethod::None,
            baseUomId: $uomId,
            isStockable: $stockable,
        ));
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
            'guard_name' => 'web',
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
            'guard_name' => 'web',
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
