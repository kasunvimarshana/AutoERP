<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\PreparedPurchaseInvoiceData;
use Modules\Purchase\DTOs\PurchaseInvoiceSourceData;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseInvoiceDtoFactory
{
    private const GOODS_RECEIPT = 'goods_receipt_note';

    private const GOODS_RECEIPT_LINE = 'goods_receipt_note_line';

    private const PURCHASE_ORDER = 'purchase_order';

    private const PURCHASE_ORDER_LINE = 'purchase_order_line';

    public function __construct(private readonly DecimalMath $math) {}

    public function prepare(
        CreatePurchaseInvoiceData $data,
        bool $lockSources = false,
    ): PreparedPurchaseInvoiceData {
        $sources = [];
        $sourceLines = [];
        $invoiceLines = [];
        $adjustments = [];
        $sourceTotals = [];
        $lineQuantities = [];
        $lineNumber = 1;
        $goodsReceipts = collect();
        $resolvedSupplierType = $data->supplierType;
        $resolvedSupplierId = $data->supplierId;

        foreach ($data->sources as $source) {
            if (! $source instanceof PurchaseInvoiceSourceData) {
                throw new InvalidArgumentException('Purchase supplier invoice sources are invalid.');
            }

            if ($source->sourceType === self::PURCHASE_ORDER) {
                $lineNumber = $this->appendPurchaseOrderSource(
                    $data,
                    $source,
                    $lineNumber,
                    $sources,
                    $sourceLines,
                    $invoiceLines,
                    $adjustments,
                    $sourceTotals,
                    $lineQuantities,
                    $lockSources,
                    $resolvedSupplierType,
                    $resolvedSupplierId,
                );

                continue;
            }

            if ($source->sourceType !== self::GOODS_RECEIPT) {
                throw new InvalidArgumentException(
                    'Purchase supplier invoices require purchase order or goods receipt note sources.',
                );
            }

            $lineNumber = $this->appendGoodsReceiptSource(
                $data,
                $source,
                $lineNumber,
                $sources,
                $sourceLines,
                $invoiceLines,
                $adjustments,
                $sourceTotals,
                $lineQuantities,
                $goodsReceipts,
                $lockSources,
                $resolvedSupplierType,
                $resolvedSupplierId,
            );
        }

        return new PreparedPurchaseInvoiceData(
            invoiceData: new CreateInvoiceData(
                tenantId: $data->tenantId,
                invoiceType: InvoiceType::Purchase,
                direction: InvoiceDirection::Inbound,
                invoiceDate: $data->invoiceDate,
                organizationUnitId: $data->organizationUnitId,
                invoiceNumber: $data->invoiceNumber,
                partyType: $resolvedSupplierType,
                partyId: $resolvedSupplierId,
                dueDate: $data->dueDate,
                currencyId: $data->currencyId,
                exchangeRate: $data->exchangeRate,
                notes: $data->notes,
                createdBy: $data->createdBy,
                lines: $invoiceLines,
                sources: $sources,
                sourceLines: $sourceLines,
                adjustments: $adjustments,
            ),
            sourceTotals: $sourceTotals,
            lineQuantities: $lineQuantities,
            goodsReceipts: $goodsReceipts,
        );
    }

    private function appendGoodsReceiptSource(
        CreatePurchaseInvoiceData $data,
        PurchaseInvoiceSourceData $source,
        int $lineNumber,
        array &$sources,
        array &$sourceLines,
        array &$invoiceLines,
        array &$adjustments,
        array &$sourceTotals,
        array &$lineQuantities,
        Collection $goodsReceipts,
        bool $lockSources,
        ?string &$resolvedSupplierType,
        ?int &$resolvedSupplierId,
    ): int {
        $grn = GoodsReceiptNote::query()
            ->with(['lines', 'adjustments'])
            ->when($lockSources, fn ($query) => $query->lockForUpdate())
            ->findOrFail($source->sourceId);
        $this->assertSourceScope(
            (int) $grn->tenant_id,
            $grn->organization_unit_id,
            $data,
        );
        [$resolvedSupplierType, $resolvedSupplierId] = $this->resolveSupplier(
            $resolvedSupplierType,
            $resolvedSupplierId,
            $grn->supplier_type,
            $grn->supplier_id,
        );

        $goodsReceipts->push($grn);
        $selectedTotal = '0.000000';

        foreach ($grn->lines as $sourceLine) {
            $quantity = $source->lineQuantities[(int) $sourceLine->getKey()]
                ?? (string) $sourceLine->remaining_quantity;
            $quantity = $this->math->normalize($quantity);
            if ($this->math->isZero($quantity)) {
                continue;
            }
            if ($this->math->compare($quantity, (string) $sourceLine->remaining_quantity) > 0) {
                throw new InvalidArgumentException(
                    'Purchase invoice quantity cannot exceed GRN remaining quantity.',
                );
            }

            $selectedTotal = $this->math->add(
                $selectedTotal,
                $this->math->mul($quantity, (string) $sourceLine->unit_price),
            );
            $lineQuantities[self::GOODS_RECEIPT_LINE.':'.$sourceLine->getKey()] = $quantity;

            $invoiceLines[] = new InvoiceLineData(
                lineNumber: $lineNumber++,
                description: $sourceLine->description ?? 'Purchase item',
                quantity: $quantity,
                unitPrice: (string) $sourceLine->unit_price,
                lineType: InvoiceLineType::Item,
                itemId: (int) $sourceLine->item_id,
                uomId: $sourceLine->uom_id,
                discountAmount: $this->proportionalAmount(
                    (string) $sourceLine->discount_amount,
                    $quantity,
                    (string) $sourceLine->accepted_quantity,
                ),
                taxAmount: $this->proportionalAmount(
                    (string) $sourceLine->tax_amount,
                    $quantity,
                    (string) $sourceLine->accepted_quantity,
                ),
                chargeAmount: $this->proportionalAmount(
                    (string) $sourceLine->charge_amount,
                    $quantity,
                    (string) $sourceLine->accepted_quantity,
                ),
                sourceLineType: self::GOODS_RECEIPT_LINE,
                sourceLineId: (int) $sourceLine->getKey(),
            );

            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $data->tenantId,
                sourceType: self::GOODS_RECEIPT,
                sourceId: (int) $grn->getKey(),
                sourceLineType: self::GOODS_RECEIPT_LINE,
                sourceLineId: (int) $sourceLine->getKey(),
                sourceQuantity: (string) $sourceLine->accepted_quantity,
                invoicedQuantity: $quantity,
                sourceUnitPrice: (string) $sourceLine->unit_price,
                sourceLineTotal: $this->math->mul(
                    (string) $sourceLine->accepted_quantity,
                    (string) $sourceLine->unit_price,
                ),
                organizationUnitId: $data->organizationUnitId,
                previouslyInvoicedQuantity: (string) $sourceLine->invoiced_quantity,
            );
        }

        $sourceAdjustmentTotal = $this->adjustmentTotal($grn->adjustments);
        $sourceTotals[self::GOODS_RECEIPT.':'.$grn->getKey()] = [
            'line_total' => $selectedTotal,
            'adjustment_total' => $sourceAdjustmentTotal,
        ];
        $sources[] = new InvoiceSourceData(
            tenantId: $data->tenantId,
            sourceType: self::GOODS_RECEIPT,
            sourceId: (int) $grn->getKey(),
            organizationUnitId: $data->organizationUnitId,
            sourceDocumentNumber: $grn->grn_number,
            sourceDocumentDate: $grn->received_date->toDateString(),
            sourceSubtotal: (string) $grn->subtotal,
            sourceAdjustmentTotal: $sourceAdjustmentTotal,
            sourceGrandTotal: (string) $grn->grand_total,
        );
        $this->appendAdjustments(
            $grn->adjustments,
            $adjustments,
            (int) $grn->getKey(),
            self::GOODS_RECEIPT,
        );

        return $lineNumber;
    }

    private function appendPurchaseOrderSource(
        CreatePurchaseInvoiceData $data,
        PurchaseInvoiceSourceData $source,
        int $lineNumber,
        array &$sources,
        array &$sourceLines,
        array &$invoiceLines,
        array &$adjustments,
        array &$sourceTotals,
        array &$lineQuantities,
        bool $lockSources,
        ?string &$resolvedSupplierType,
        ?int &$resolvedSupplierId,
    ): int {
        $order = PurchaseOrder::query()
            ->with(['lines', 'adjustments'])
            ->when($lockSources, fn ($query) => $query->lockForUpdate())
            ->findOrFail($source->sourceId);
        $this->assertSourceScope(
            (int) $order->tenant_id,
            $order->organization_unit_id,
            $data,
        );
        [$resolvedSupplierType, $resolvedSupplierId] = $this->resolveSupplier(
            $resolvedSupplierType,
            $resolvedSupplierId,
            $order->supplier_type,
            $order->supplier_id,
        );

        $selectedTotal = '0.000000';
        foreach ($order->lines as $sourceLine) {
            /** @var PurchaseOrderLine $sourceLine */
            $remaining = $this->math->sub(
                (string) $sourceLine->ordered_quantity,
                (string) $sourceLine->invoiced_quantity,
            );
            $remaining = $this->math->sub($remaining, (string) $sourceLine->cancelled_quantity);
            $quantity = $source->lineQuantities[(int) $sourceLine->getKey()] ?? $remaining;
            $quantity = $this->math->normalize($quantity);

            if ($this->math->isZero($quantity)) {
                continue;
            }
            if ($this->math->compare($quantity, $remaining) > 0) {
                throw new InvalidArgumentException(
                    'Purchase invoice quantity cannot exceed PO remaining quantity.',
                );
            }

            $selectedTotal = $this->math->add(
                $selectedTotal,
                $this->math->mul($quantity, (string) $sourceLine->unit_price),
            );
            $lineQuantities[self::PURCHASE_ORDER_LINE.':'.$sourceLine->getKey()] = $quantity;

            $invoiceLines[] = new InvoiceLineData(
                lineNumber: $lineNumber++,
                description: $sourceLine->description ?? 'Purchase item',
                quantity: $quantity,
                unitPrice: (string) $sourceLine->unit_price,
                lineType: InvoiceLineType::Item,
                itemId: (int) $sourceLine->item_id,
                uomId: $sourceLine->uom_id,
                discountAmount: $this->proportionalAmount(
                    (string) $sourceLine->discount_amount,
                    $quantity,
                    (string) $sourceLine->ordered_quantity,
                ),
                taxAmount: $this->proportionalAmount(
                    (string) $sourceLine->tax_amount,
                    $quantity,
                    (string) $sourceLine->ordered_quantity,
                ),
                chargeAmount: $this->proportionalAmount(
                    (string) $sourceLine->charge_amount,
                    $quantity,
                    (string) $sourceLine->ordered_quantity,
                ),
                sourceLineType: self::PURCHASE_ORDER_LINE,
                sourceLineId: (int) $sourceLine->getKey(),
            );

            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $data->tenantId,
                sourceType: self::PURCHASE_ORDER,
                sourceId: (int) $order->getKey(),
                sourceLineType: self::PURCHASE_ORDER_LINE,
                sourceLineId: (int) $sourceLine->getKey(),
                sourceQuantity: (string) $sourceLine->ordered_quantity,
                invoicedQuantity: $quantity,
                sourceUnitPrice: (string) $sourceLine->unit_price,
                sourceLineTotal: (string) $sourceLine->line_subtotal,
                organizationUnitId: $data->organizationUnitId,
                previouslyInvoicedQuantity: (string) $sourceLine->invoiced_quantity,
            );
        }

        $sourceAdjustmentTotal = $this->adjustmentTotal($order->adjustments);
        $sourceTotals[self::PURCHASE_ORDER.':'.$order->getKey()] = [
            'line_total' => $selectedTotal,
            'adjustment_total' => $sourceAdjustmentTotal,
        ];
        $sources[] = new InvoiceSourceData(
            tenantId: $data->tenantId,
            sourceType: self::PURCHASE_ORDER,
            sourceId: (int) $order->getKey(),
            organizationUnitId: $data->organizationUnitId,
            sourceDocumentNumber: $order->purchase_order_number,
            sourceDocumentDate: $order->purchase_order_date->toDateString(),
            sourceSubtotal: (string) $order->subtotal,
            sourceAdjustmentTotal: $sourceAdjustmentTotal,
            sourceGrandTotal: (string) $order->grand_total,
        );
        $this->appendAdjustments(
            $order->adjustments,
            $adjustments,
            (int) $order->getKey(),
            self::PURCHASE_ORDER,
        );

        return $lineNumber;
    }

    private function assertSourceScope(
        int $sourceTenantId,
        ?int $sourceOrganizationUnitId,
        CreatePurchaseInvoiceData $data,
    ): void {
        if ($sourceTenantId !== $data->tenantId) {
            throw new InvalidArgumentException('Purchase invoice source belongs to a different tenant.');
        }

        if ($data->organizationUnitId !== null
            && $sourceOrganizationUnitId !== null
            && (int) $sourceOrganizationUnitId !== $data->organizationUnitId) {
            throw new InvalidArgumentException(
                'Purchase invoice source belongs to a different organization unit.',
            );
        }
    }

    private function appendAdjustments(
        Collection $sourceAdjustments,
        array &$invoiceAdjustments,
        int $sourceId,
        string $sourceType,
    ): void {
        foreach ($sourceAdjustments as $adjustment) {
            if ($adjustment instanceof PurchaseHeaderAdjustment && (bool) $adjustment->is_allocatable) {
                $invoiceAdjustments[] = $this->toInvoiceAdjustment(
                    $adjustment,
                    $sourceId,
                    $sourceType,
                );
            }
        }
    }

    private function toInvoiceAdjustment(
        PurchaseHeaderAdjustment $adjustment,
        int $sourceId,
        string $sourceType,
    ): InvoiceAdjustmentData {
        return new InvoiceAdjustmentData(
            name: (string) $adjustment->name,
            adjustmentType: $this->toInvoiceAdjustmentType($adjustment),
            effect: AdjustmentEffect::from($adjustment->effect->value),
            amount: (string) $adjustment->amount,
            sourceAdjustmentType: 'purchase_header_adjustment',
            sourceAdjustmentId: (int) $adjustment->getKey(),
            sourceType: $sourceType,
            sourceId: $sourceId,
            calculationType: $adjustment->calculation_type->value,
            rate: (string) $adjustment->rate,
            sourceAmount: (string) $adjustment->amount,
            allocationMethod: AllocationMethod::from($adjustment->allocation_method->value),
            isSystemGenerated: true,
            description: $adjustment->description,
        );
    }

    private function adjustmentTotal(Collection $adjustments): string
    {
        $total = '0.000000';
        foreach ($adjustments as $adjustment) {
            if (! $adjustment instanceof PurchaseHeaderAdjustment) {
                continue;
            }
            $total = $adjustment->effect->value === 'increase'
                ? $this->math->add($total, (string) $adjustment->amount)
                : $this->math->sub($total, (string) $adjustment->amount);
        }

        return $total;
    }

    private function toInvoiceAdjustmentType(PurchaseHeaderAdjustment $adjustment): AdjustmentType
    {
        return match ($adjustment->adjustment_type->value) {
            'discount' => AdjustmentType::Discount,
            'tax' => AdjustmentType::Tax,
            'freight' => AdjustmentType::Freight,
            'charge', 'insurance', 'service_charge', 'duty', 'levy' => AdjustmentType::Charge,
            'credit_note' => AdjustmentType::CreditNote,
            'debit_note' => AdjustmentType::DebitNote,
            'withholding' => AdjustmentType::Withholding,
            'rounding' => AdjustmentType::Rounding,
            default => AdjustmentType::Other,
        };
    }

    private function proportionalAmount(
        string $amount,
        string $selectedQuantity,
        string $sourceQuantity,
    ): string {
        if ($this->math->isZero($amount) || $this->math->isZero($sourceQuantity)) {
            return '0.000000';
        }

        return $this->math->mul(
            $amount,
            $this->math->div($selectedQuantity, $sourceQuantity, 12),
        );
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function resolveSupplier(
        ?string $selectedType,
        ?int $selectedId,
        mixed $sourceType,
        mixed $sourceId,
    ): array {
        $resolvedType = trim((string) $sourceType);
        $resolvedId = is_numeric($sourceId) ? (int) $sourceId : null;

        if ($resolvedType === '' || $resolvedId === null || $resolvedId < 1) {
            throw new InvalidArgumentException('Purchase invoice source requires a supplier.');
        }

        if (($selectedType !== null && $selectedType !== $resolvedType)
            || ($selectedId !== null && $selectedId !== $resolvedId)
        ) {
            throw new InvalidArgumentException(
                'All purchase invoice sources must belong to the selected supplier.',
            );
        }

        return [$resolvedType, $resolvedId];
    }
}
