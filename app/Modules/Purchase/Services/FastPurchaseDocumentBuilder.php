<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\DTOs\PurchaseInvoiceSourceData;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;
use Modules\Purchase\Models\GoodsReceiptNote;

final class FastPurchaseDocumentBuilder
{
    private const SUPPLIER_TYPE = 'supplier';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly GoodsReceiptNoteService $goodsReceipts,
        private readonly PurchaseInvoiceIntegrationService $purchaseInvoices,
        private readonly PurchasePaymentIntegrationService $purchasePayments,
        private readonly PaymentCreationService $payments,
    ) {}

    /**
     * @param  array<string, mixed>  $resolved
     */
    public function createGoodsReceipt(array $resolved): GoodsReceiptNote
    {
        $lines = [];
        foreach ($resolved['lines'] as $line) {
            if (! (bool) $line['is_stock']) {
                continue;
            }

            $lines[] = new GoodsReceiptNoteLineData(
                itemId: (int) $line['item_id'],
                receivedQuantity: $line['quantity'],
                acceptedQuantity: $line['quantity'],
                unitPrice: $line['unit_cost'],
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
            );
        }

        $grn = $this->goodsReceipts->create(new CreateGoodsReceiptNoteData(
            tenantId: (int) $resolved['tenant_id'],
            receivedDate: (string) $resolved['purchase_date'],
            warehouseId: (int) $resolved['warehouse_id'],
            organizationUnitId: $resolved['organization_unit_id'],
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

    /**
     * @param  array<string, mixed>  $resolved
     */
    public function createSupplierInvoice(array $resolved, ?GoodsReceiptNote $goodsReceipt): Invoice
    {
        $directLines = [];
        foreach ($resolved['lines'] as $line) {
            if ((bool) $line['is_stock']) {
                continue;
            }

            $directLines[] = new InvoiceLineData(
                lineNumber: count($directLines) + 1,
                description: $line['description'],
                quantity: $line['quantity'],
                unitPrice: $line['unit_cost'],
                lineType: InvoiceLineType::Item,
                itemId: (int) $line['item_id'],
                uomId: $line['uom_id'],
                discountAmount: $line['discount_amount'],
                taxAmount: $line['non_withholding_tax_amount'],
                metadata: [
                    'fast_purchase' => true,
                    'supplier_reference' => $resolved['supplier_reference'],
                    'tax_group_id' => $line['tax_group_id'],
                    'is_stock' => false,
                ],
            );
        }

        $sources = $goodsReceipt instanceof GoodsReceiptNote
            ? [new PurchaseInvoiceSourceData('goods_receipt_note', (int) $goodsReceipt->getKey())]
            : [];

        $adjustments = array_map(
            fn (array $adjustment): InvoiceAdjustmentData => $this->invoiceAdjustmentData($adjustment),
            $resolved['adjustments'],
        );
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
            directLines: $directLines,
            adjustments: $adjustments,
        ))->load(['lines', 'sources', 'sourceLines', 'adjustments', 'balance', 'supplier']);
    }

    /**
     * @param  array<string, mixed>  $resolved
     */
    public function createSupplierPayment(array $resolved, Invoice $invoice): Payment
    {
        $payment = $resolved['payment'];
        $amount = (string) $payment['amount'];

        $data = $this->purchasePayments->prepareSupplierPayment(
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
            allocations: [
                new PaymentAllocationData(
                    invoiceId: (int) $invoice->getKey(),
                    allocatedAmount: $amount,
                    allocationDate: (string) $resolved['purchase_date'],
                    allowOverpayment: false,
                    metadata: ['fast_purchase' => true, 'supplier_reference' => $resolved['supplier_reference']],
                ),
            ],
            status: PaymentStatus::Posted,
            createdBy: $resolved['current_user_id'],
            notes: $resolved['notes'],
            bankAccountId: $payment['header_bank_account_id'],
            metadata: ['fast_purchase' => true, 'supplier_reference' => $resolved['supplier_reference']],
        );

        return $this->payments->create($data)->load(['lines', 'allocations']);
    }

    /**
     * @param  array{data: PurchaseHeaderAdjustmentData, amount: string}  $adjustment
     */
    private function invoiceAdjustmentData(array $adjustment): InvoiceAdjustmentData
    {
        $data = $adjustment['data'];

        return new InvoiceAdjustmentData(
            name: $data->name,
            adjustmentType: $this->invoiceAdjustmentType($data->adjustmentType),
            effect: $data->effect === PurchaseAdjustmentEffect::Increase ? AdjustmentEffect::Increase : AdjustmentEffect::Decrease,
            amount: $adjustment['amount'],
            calculationType: $data->calculationType->value,
            rate: $data->rate,
            sourceAmount: $adjustment['amount'],
            allocationMethod: AllocationMethod::Manual,
            isSystemGenerated: false,
            description: $data->description,
        );
    }

    private function invoiceAdjustmentType(PurchaseAdjustmentType $type): AdjustmentType
    {
        return match ($type) {
            PurchaseAdjustmentType::Discount => AdjustmentType::Discount,
            PurchaseAdjustmentType::Tax => AdjustmentType::Tax,
            PurchaseAdjustmentType::Freight => AdjustmentType::Freight,
            PurchaseAdjustmentType::CreditNote => AdjustmentType::CreditNote,
            PurchaseAdjustmentType::DebitNote => AdjustmentType::DebitNote,
            PurchaseAdjustmentType::Withholding => AdjustmentType::Withholding,
            PurchaseAdjustmentType::Rounding => AdjustmentType::Rounding,
            PurchaseAdjustmentType::Other,
            PurchaseAdjustmentType::Custom => AdjustmentType::Other,
            default => AdjustmentType::Charge,
        };
    }
}
