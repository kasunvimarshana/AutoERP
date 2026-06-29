<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\Models\Payment;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\DTOs\PurchaseInvoiceSourceData;
use Modules\Purchase\DTOs\PurchaseOrderLineData;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class FastPurchaseDocumentBuilder
{
    private const SUPPLIER_TYPE = 'supplier';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseOrderService $purchaseOrders,
        private readonly GoodsReceiptNoteService $goodsReceipts,
        private readonly PurchaseInvoiceIntegrationService $purchaseInvoices,
        private readonly PurchasePaymentIntegrationService $purchasePayments,
    ) {}

    public function createPurchaseOrder(array $resolved): PurchaseOrder
    {
        $lines = array_map(
            static fn (array $line): PurchaseOrderLineData => new PurchaseOrderLineData(
                itemId: (int) $line['item_id'],
                orderedQuantity: $line['quantity'],
                unitPrice: $line['unit_cost'],
                itemVariantId: $line['item_variant_id'],
                description: $line['description'],
                uomId: $line['uom_id'],
                orderedUomId: $line['uom_id'],
                baseUomId: $line['base_uom_id'],
                uomConversionFactor: $line['uom_conversion_factor'],
                baseQuantity: $line['base_quantity'],
                discountCalculationType: $line['discount_calculation_type'],
                discountRate: $line['discount_rate'],
                discountAmount: $line['discount_amount'],
                taxAmount: $line['non_withholding_tax_amount'],
                chargeCalculationType: $line['charge_calculation_type'],
                chargeRate: $line['charge_rate'],
                chargeAmount: $line['charge_amount'],
                taxGroupId: $line['tax_group_id'],
                clientLineKey: $line['client_line_key'] ?? null,
            ),
            $resolved['lines'],
        );

        $order = $this->purchaseOrders->create(new CreatePurchaseOrderData(
            tenantId: (int) $resolved['tenant_id'],
            purchaseOrderDate: (string) $resolved['purchase_date'],
            organizationUnitId: $resolved['organization_unit_id'],
            supplierType: self::SUPPLIER_TYPE,
            supplierId: (int) $resolved['supplier']->getKey(),
            warehouseId: (int) $resolved['warehouse_id'],
            warehouseLocationId: $resolved['warehouse_location_id'],
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            notes: $resolved['notes'],
            createdBy: $resolved['current_user_id'],
            lines: $lines,
            adjustments: array_map(
                static fn (array $adjustment): PurchaseHeaderAdjustmentData => $adjustment['data'],
                $resolved['adjustments'],
            ),
        ));

        $order = $this->purchaseOrders->submit($order, $resolved['current_user_id']);

        return $this->purchaseOrders->approve($order, $resolved['current_user_id'])
            ->load(['lines', 'adjustments', 'supplier', 'warehouse', 'warehouseLocation']);
    }

    public function createGoodsReceipt(array $resolved, PurchaseOrder $purchaseOrder): GoodsReceiptNote
    {
        $orderLines = $this->orderLinesByFastLine($purchaseOrder);
        $lines = [];
        foreach ($resolved['lines'] as $line) {
            if (! (bool) $line['is_stock']) {
                continue;
            }
            $poLine = $orderLines[$line['line_number']] ?? null;
            if (! $poLine instanceof PurchaseOrderLine) {
                throw new \InvalidArgumentException('Fast purchase stock line could not be matched to the created purchase order line.');
            }

            $lines[] = new GoodsReceiptNoteLineData(
                itemId: (int) $line['item_id'],
                receivedQuantity: $line['quantity'],
                acceptedQuantity: $line['quantity'],
                unitPrice: $line['unit_cost'],
                purchaseOrderLineId: (int) $poLine->getKey(),
                itemVariantId: $line['item_variant_id'],
                description: $line['description'],
                uomId: $line['uom_id'],
                orderedUomId: $line['uom_id'],
                baseUomId: $line['base_uom_id'],
                uomConversionFactor: $line['uom_conversion_factor'],
                baseReceivedQuantity: $line['base_quantity'],
                baseAcceptedQuantity: $line['base_quantity'],
                orderedQuantity: $line['quantity'],
                discountAmount: $line['discount_amount'],
                taxAmount: $line['non_withholding_tax_amount'],
                chargeAmount: $line['charge_amount'],
                taxGroupId: $line['tax_group_id'],
            );
        }

        $grn = $this->goodsReceipts->create(new CreateGoodsReceiptNoteData(
            tenantId: (int) $resolved['tenant_id'],
            receivedDate: (string) $resolved['purchase_date'],
            warehouseId: (int) $resolved['warehouse_id'],
            organizationUnitId: $resolved['organization_unit_id'],
            purchaseOrderId: (int) $purchaseOrder->getKey(),
            warehouseLocationId: $resolved['warehouse_location_id'],
            supplierType: self::SUPPLIER_TYPE,
            supplierId: (int) $resolved['supplier']->getKey(),
            notes: $resolved['notes'],
            receivedBy: $resolved['current_user_id'],
            lines: $lines,
        ));

        return $this->goodsReceipts->post($grn, $resolved['current_user_id'])
            ->load(['lines.inventoryMovement', 'supplier', 'warehouse', 'warehouseLocation']);
    }

    public function createSupplierInvoice(array $resolved, PurchaseOrder $purchaseOrder, ?GoodsReceiptNote $goodsReceipt): Invoice
    {
        $sources = [];
        if ($goodsReceipt instanceof GoodsReceiptNote) {
            $sources[] = new PurchaseInvoiceSourceData(
                'goods_receipt_note',
                (int) $goodsReceipt->getKey(),
                $this->goodsReceiptSourceQuantities($goodsReceipt),
            );
        }

        $poSourceQuantities = $this->purchaseOrderSourceQuantitiesForNonStock($purchaseOrder, $resolved);
        if ($poSourceQuantities !== []) {
            $sources[] = new PurchaseInvoiceSourceData(
                'purchase_order',
                (int) $purchaseOrder->getKey(),
                $poSourceQuantities,
            );
        }

        $adjustments = [];
        if ($this->math->compare($resolved['summary']['line_withholding_total'], '0.000000') > 0) {
            $adjustments[] = new InvoiceAdjustmentData(
                name: 'Withholding',
                adjustmentType: AdjustmentType::Withholding,
                effect: AdjustmentEffect::Decrease,
                amount: $resolved['summary']['line_withholding_total'],
                calculationType: 'fixed',
                rate: '0.000000',
                sourceAmount: $resolved['summary']['line_withholding_total'],
                allocationMethod: AllocationMethod::Manual,
                isSystemGenerated: true,
                description: 'Fast purchase withholding',
            );
        }

        return $this->purchaseInvoices->createSupplierInvoice(new CreatePurchaseInvoiceData(
            tenantId: (int) $resolved['tenant_id'],
            invoiceDate: (string) $resolved['purchase_date'],
            organizationUnitId: $resolved['organization_unit_id'],
            supplierType: self::SUPPLIER_TYPE,
            supplierId: (int) $resolved['supplier']->getKey(),
            dueDate: $resolved['due_date'],
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            notes: $resolved['notes'],
            createdBy: $resolved['current_user_id'],
            sources: $sources,
            status: InvoiceStatus::Posted,
            directLines: [],
            adjustments: $adjustments,
        ))->load(['lines', 'sources', 'sourceLines', 'adjustments', 'balance', 'supplier']);
    }

    public function createSupplierPayment(array $resolved, Invoice $invoice): Payment
    {
        $payment = $resolved['payment'];
        $amount = (string) $payment['amount'];

        return $this->purchasePayments->createSupplierPayment(
            tenantId: (int) $resolved['tenant_id'],
            paymentDate: (string) $resolved['purchase_date'],
            amount: $amount,
            organizationUnitId: $resolved['organization_unit_id'],
            supplierType: self::SUPPLIER_TYPE,
            supplierId: (int) $resolved['supplier']->getKey(),
            currencyId: $resolved['currency_id'],
            exchangeRate: (string) $resolved['exchange_rate'],
            referenceNumber: $payment['reference'] ?? $resolved['supplier_reference'],
            lines: $payment['lines'],
            allocations: [new PaymentAllocationData(
                invoiceId: (int) $invoice->getKey(),
                allocatedAmount: $amount,
                allocationDate: (string) $resolved['purchase_date'],
                metadata: ['fast_purchase' => true, 'supplier_reference' => $resolved['supplier_reference']],
            )],
            createdBy: $resolved['current_user_id'],
            notes: $resolved['notes'],
        )->load(['lines', 'allocations', 'lifecycleEvents']);
    }

    private function orderLinesByFastLine(PurchaseOrder $purchaseOrder): array
    {
        $purchaseOrder->loadMissing('lines');

        return $purchaseOrder->lines
            ->keyBy(fn (PurchaseOrderLine $line): int => (int) $line->line_number)
            ->all();
    }

    private function goodsReceiptSourceQuantities(GoodsReceiptNote $goodsReceipt): array
    {
        $goodsReceipt->loadMissing('lines');

        return $goodsReceipt->lines
            ->mapWithKeys(fn ($line): array => [(int) $line->getKey() => (string) $line->accepted_quantity])
            ->all();
    }

    private function purchaseOrderSourceQuantitiesForNonStock(PurchaseOrder $purchaseOrder, array $resolved): array
    {
        $orderLines = $this->orderLinesByFastLine($purchaseOrder);
        $quantities = [];
        foreach ($resolved['lines'] as $line) {
            if ((bool) $line['is_stock']) {
                continue;
            }

            $poLine = $orderLines[$line['line_number']] ?? null;
            if (! $poLine instanceof PurchaseOrderLine) {
                throw new \InvalidArgumentException('Fast purchase non-stock line could not be matched to the created purchase order line.');
            }

            $quantities[(int) $poLine->getKey()] = $line['quantity'];
        }

        return $quantities;
    }
}
