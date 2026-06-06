<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Finance\DTOs\JournalLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentType;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\DTOs\PurchaseInvoiceSourceData;
use Modules\Purchase\DTOs\PurchaseOrderLineData;
use Modules\Purchase\DTOs\PurchaseReturnLineData;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseDebitNote;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Services\GoodsReceiptNoteService;
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
        $this->assertCount(3, $order->adjustments);
    }

    public function test_partial_grn_posts_inventory_and_skips_service_items(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $service = $this->createItem($tenantId, 'SVC-'.Str::upper(Str::random(4)), ItemType::Service, false);
        $order = app(PurchaseOrderService::class)->create(new CreatePurchaseOrderData(
            tenantId: $tenantId,
            purchaseOrderDate: '2026-06-06',
            warehouseId: $warehouseId,
            lines: [
                new PurchaseOrderLineData((int) $item->getKey(), '10.000000', '100.000000'),
                new PurchaseOrderLineData((int) $service->getKey(), '1.000000', '50.000000'),
            ],
        ));

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
        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $order->refresh()->status);
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
            supplierId: 10,
            sources: [
                new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grnOne->getKey()),
                new PurchaseInvoiceSourceData('goods_receipt_note', (int) $grnTwo->getKey()),
            ],
        ));

        $this->assertSame('123000.000000', (string) $invoice->grand_total);
        $this->assertCount(2, PurchaseInvoiceLink::query()->where('invoice_id', $invoice->getKey())->get());
        $this->assertCount(6, $invoice->adjustmentAllocations);
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
        $this->assertSame(GoodsReceiptNoteStatus::Invoiced, $grn->refresh()->status);
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
        $this->assertSame(3, PurchaseHeaderAdjustment::query()->where('source_type', 'goods_receipt_note')->where('source_id', $grn->getKey())->count());
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
        $this->assertSame('9840.000000', (string) PurchaseDebitNote::query()->firstOrFail()->amount);
        $this->assertSame(1, InventoryMovement::query()->where('source_type', 'purchase_return')->count());
    }

    public function test_tenant_isolation_is_enforced(): void
    {
        [$tenantId, $warehouseId, $item] = $this->purchaseContext();
        $otherTenant = $this->createTenant('OTHER');
        $otherWarehouse = $this->createWarehouse($otherTenant, 'WH-OTHER');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Purchase reference belongs to a different tenant.');

        app(PurchaseOrderService::class)->create(new CreatePurchaseOrderData(
            tenantId: $tenantId,
            purchaseOrderDate: '2026-06-06',
            warehouseId: $otherWarehouse,
            lines: [new PurchaseOrderLineData((int) $item->getKey(), '1.000000', '1.000000')],
        ));
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
            lines: [new JournalLineData(accountId: 1, lineNumber: 1, debit: '100.000000')],
        );

        $this->assertSame(PaymentType::SupplierPayment, $payment->paymentType);
        $this->assertSame(PaymentDirection::Outbound, $payment->direction);
        $this->assertSame('purchase_order', $journal->source?->sourceType);
    }

    private function createAdjustedOrder(int $tenantId, int $warehouseId, Item $item): PurchaseOrder
    {
        return app(PurchaseOrderService::class)->create(new CreatePurchaseOrderData(
            tenantId: $tenantId,
            purchaseOrderDate: '2026-06-06',
            supplierType: 'supplier',
            supplierId: 10,
            warehouseId: $warehouseId,
            lines: [
                new PurchaseOrderLineData((int) $item->getKey(), '100.000000', '1000.000000'),
            ],
            adjustments: [
                new PurchaseHeaderAdjustmentData('Discount', PurchaseAdjustmentType::Discount, PurchaseAdjustmentEffect::Decrease, '5000.000000'),
                new PurchaseHeaderAdjustmentData('VAT', PurchaseAdjustmentType::Tax, PurchaseAdjustmentEffect::Increase, '18000.000000'),
                new PurchaseHeaderAdjustmentData('Freight', PurchaseAdjustmentType::Freight, PurchaseAdjustmentEffect::Increase, '10000.000000'),
            ],
        ));
    }

    /**
     * @return array{GoodsReceiptNote, GoodsReceiptNote}
     */
    private function receiveOrderInTwoParts(PurchaseOrder $order, int $warehouseId, Item $item): array
    {
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

    private function purchaseContext(): array
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-'.Str::upper(Str::random(4)));
        $item = $this->createItem($tenantId, 'ITEM-'.Str::upper(Str::random(4)));

        return [$tenantId, $warehouseId, $item];
    }

    private function createItem(int $tenantId, string $code, ItemType $type = ItemType::Stock, bool $stockable = true): Item
    {
        return app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            code: $code,
            name: 'Purchase '.$code,
            itemType: $type,
            trackingType: TrackingType::None,
            costingMethod: CostingMethod::Fifo,
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
            'is_active' => true,
            'is_isolated' => true,
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
}
