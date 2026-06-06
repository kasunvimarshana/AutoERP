<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\DTOs\CreateInvoiceData;
use Modules\Invoice\DTOs\InvoiceAdjustmentData;
use Modules\Invoice\DTOs\InvoiceCalculationResult;
use Modules\Invoice\DTOs\InvoiceLineData;
use Modules\Invoice\DTOs\InvoiceSourceData;
use Modules\Invoice\DTOs\InvoiceSourceLineData;
use Modules\Invoice\Enums\AdjustmentEffect;
use Modules\Invoice\Enums\AdjustmentType;
use Modules\Invoice\Enums\AllocationMethod;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceCreationService;
use Modules\Purchase\DTOs\CreatePurchaseInvoiceData;
use Modules\Purchase\DTOs\PurchaseInvoiceSourceData;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseInvoiceIntegrationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoiceCreationService $invoices,
        private readonly PurchaseOrderService $orders,
    ) {}

    public function createSupplierInvoice(CreatePurchaseInvoiceData $data): Invoice
    {
        return DB::transaction(function () use ($data): Invoice {
            $normalized = $this->normalizeInvoiceData($data);
            $invoice = $this->invoices->create($normalized['invoiceData']);

            foreach ($normalized['sourceTotals'] as $sourceKey => $totals) {
                [$sourceType, $sourceId] = explode(':', $sourceKey, 2);
                PurchaseInvoiceLink::query()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'invoice_id' => $invoice->getKey(),
                    'source_type' => $sourceType,
                    'source_id' => (int) $sourceId,
                    'source_line_total' => $totals['line_total'],
                    'allocated_adjustment_total' => $totals['adjustment_total'],
                    'invoice_total' => (string) $invoice->grand_total,
                    'status' => 'active',
                ]);
            }

            foreach ($normalized['lineQuantities'] as $lineKey => $quantity) {
                [$lineType, $lineId] = explode(':', (string) $lineKey, 2);
                if ($lineType === 'goods_receipt_note_line') {
                    $line = GoodsReceiptNoteLine::query()->findOrFail((int) $lineId);
                    $line->invoiced_quantity = $this->math->add((string) $line->invoiced_quantity, $quantity);
                    $line->remaining_quantity = $this->math->sub((string) $line->accepted_quantity, (string) $line->invoiced_quantity);
                    $line->save();

                    if ($line->purchaseOrderLine !== null) {
                        $this->orders->applyInvoiced($line->purchaseOrderLine, $quantity);
                    }

                    continue;
                }

                if ($lineType === 'purchase_order_line') {
                    $this->orders->applyInvoiced(PurchaseOrderLine::query()->findOrFail((int) $lineId), $quantity);
                }
            }

            $this->refreshGrnInvoiceStatuses($normalized['grns']);

            return $invoice;
        });
    }

    public function previewSupplierInvoice(CreatePurchaseInvoiceData $data): InvoiceCalculationResult
    {
        $normalized = $this->normalizeInvoiceData($data);

        return $this->invoices->preview($normalized['invoiceData']);
    }

    /**
     * @return array{invoiceData: CreateInvoiceData, sourceTotals: array<string, array{line_total: string, adjustment_total: string}>, lineQuantities: array<string, string>, grns: Collection<int, GoodsReceiptNote>, purchaseOrders: Collection<int, PurchaseOrder>}
     */
    private function normalizeInvoiceData(CreatePurchaseInvoiceData $data): array
    {
        $sources = [];
        $sourceLines = [];
        $invoiceLines = [];
        $adjustments = [];
        $sourceTotals = [];
        $lineQuantities = [];
        $lineNumber = 1;
        $grns = collect();
        $purchaseOrders = collect();

        foreach ($data->sources as $source) {
            if (! $source instanceof PurchaseInvoiceSourceData) {
                throw new InvalidArgumentException('Purchase supplier invoice sources are invalid.');
            }

            if ($source->sourceType === 'purchase_order') {
                $lineNumber = $this->appendPurchaseOrderSource($data, $source, $lineNumber, $sources, $sourceLines, $invoiceLines, $adjustments, $sourceTotals, $lineQuantities, $purchaseOrders);
                continue;
            }

            if ($source->sourceType !== 'goods_receipt_note') {
                throw new InvalidArgumentException('Purchase supplier invoices require purchase order or goods receipt note sources.');
            }

            $grn = GoodsReceiptNote::query()->with(['lines', 'adjustments'])->findOrFail($source->sourceId);
            if ((int) $grn->tenant_id !== $data->tenantId) {
                throw new InvalidArgumentException('Purchase invoice source belongs to a different tenant.');
            }
            if ($data->organizationUnitId !== null && $grn->organization_unit_id !== null && (int) $grn->organization_unit_id !== $data->organizationUnitId) {
                throw new InvalidArgumentException('Purchase invoice source belongs to a different organization unit.');
            }

            $grns->push($grn);
            $selectedTotal = '0.000000';

            foreach ($grn->lines as $sourceLine) {
                $quantity = $source->lineQuantities[(int) $sourceLine->getKey()] ?? (string) $sourceLine->remaining_quantity;
                $quantity = $this->math->normalize($quantity);
                if ($this->math->isZero($quantity)) {
                    continue;
                }
                if ($this->math->compare($quantity, (string) $sourceLine->remaining_quantity) > 0) {
                    throw new InvalidArgumentException('Purchase invoice quantity cannot exceed GRN remaining quantity.');
                }

                $lineBase = $this->math->mul($quantity, (string) $sourceLine->unit_price);
                $selectedTotal = $this->math->add($selectedTotal, $lineBase);
                $lineQuantities['goods_receipt_note_line:'.$sourceLine->getKey()] = $quantity;

                $invoiceLines[] = new InvoiceLineData(
                    lineNumber: $lineNumber++,
                    description: $sourceLine->description ?? 'Purchase item',
                    quantity: $quantity,
                    unitPrice: (string) $sourceLine->unit_price,
                    lineType: InvoiceLineType::Item,
                    itemId: (int) $sourceLine->item_id,
                    uomId: $sourceLine->uom_id,
                    discountAmount: $this->proportionalAmount((string) $sourceLine->discount_amount, $quantity, (string) $sourceLine->accepted_quantity),
                    taxAmount: $this->proportionalAmount((string) $sourceLine->tax_amount, $quantity, (string) $sourceLine->accepted_quantity),
                    chargeAmount: $this->proportionalAmount((string) $sourceLine->charge_amount, $quantity, (string) $sourceLine->accepted_quantity),
                    sourceLineType: 'goods_receipt_note_line',
                    sourceLineId: (int) $sourceLine->getKey(),
                );

                $sourceLines[] = new InvoiceSourceLineData(
                    tenantId: $data->tenantId,
                    sourceType: 'goods_receipt_note',
                    sourceId: (int) $grn->getKey(),
                    sourceLineType: 'goods_receipt_note_line',
                    sourceLineId: (int) $sourceLine->getKey(),
                    sourceQuantity: (string) $sourceLine->accepted_quantity,
                    invoicedQuantity: $quantity,
                    sourceUnitPrice: (string) $sourceLine->unit_price,
                    sourceLineTotal: $this->math->mul((string) $sourceLine->accepted_quantity, (string) $sourceLine->unit_price),
                    organizationUnitId: $data->organizationUnitId,
                    previouslyInvoicedQuantity: (string) $sourceLine->invoiced_quantity,
                );
            }

            $sourceAdjustmentTotal = $this->adjustmentTotal($grn->adjustments);
            $sourceKey = 'goods_receipt_note:'.$grn->getKey();
            $sourceTotals[$sourceKey] = [
                'line_total' => $selectedTotal,
                'adjustment_total' => $sourceAdjustmentTotal,
            ];

            $sources[] = new InvoiceSourceData(
                tenantId: $data->tenantId,
                sourceType: 'goods_receipt_note',
                sourceId: (int) $grn->getKey(),
                organizationUnitId: $data->organizationUnitId,
                sourceDocumentNumber: $grn->grn_number,
                sourceDocumentDate: $grn->received_date->toDateString(),
                sourceSubtotal: (string) $grn->subtotal,
                sourceAdjustmentTotal: $sourceAdjustmentTotal,
                sourceGrandTotal: (string) $grn->grand_total,
            );

            foreach ($grn->adjustments as $adjustment) {
                if ($adjustment instanceof PurchaseHeaderAdjustment && (bool) $adjustment->is_allocatable) {
                    $adjustments[] = $this->toInvoiceAdjustment($adjustment, (int) $grn->getKey());
                }
            }
        }

        return [
            'invoiceData' => new CreateInvoiceData(
                tenantId: $data->tenantId,
                invoiceType: InvoiceType::Purchase,
                direction: InvoiceDirection::Inbound,
                invoiceDate: $data->invoiceDate,
                organizationUnitId: $data->organizationUnitId,
                invoiceNumber: $data->invoiceNumber,
                partyType: $data->supplierType,
                partyId: $data->supplierId,
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
            'sourceTotals' => $sourceTotals,
            'lineQuantities' => $lineQuantities,
            'grns' => $grns,
            'purchaseOrders' => $purchaseOrders,
        ];
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
        Collection $purchaseOrders,
    ): int {
        $order = PurchaseOrder::query()->with(['lines', 'adjustments'])->findOrFail($source->sourceId);
        if ((int) $order->tenant_id !== $data->tenantId) {
            throw new InvalidArgumentException('Purchase invoice source belongs to a different tenant.');
        }
        if ($data->organizationUnitId !== null && $order->organization_unit_id !== null && (int) $order->organization_unit_id !== $data->organizationUnitId) {
            throw new InvalidArgumentException('Purchase invoice source belongs to a different organization unit.');
        }

        $purchaseOrders->push($order);
        $selectedTotal = '0.000000';

        foreach ($order->lines as $sourceLine) {
            /** @var PurchaseOrderLine $sourceLine */
            $remaining = $this->math->sub((string) $sourceLine->ordered_quantity, (string) $sourceLine->invoiced_quantity);
            $remaining = $this->math->sub($remaining, (string) $sourceLine->cancelled_quantity);
            $quantity = $source->lineQuantities[(int) $sourceLine->getKey()] ?? $remaining;
            $quantity = $this->math->normalize($quantity);
            if ($this->math->isZero($quantity)) {
                continue;
            }
            if ($this->math->compare($quantity, $remaining) > 0) {
                throw new InvalidArgumentException('Purchase invoice quantity cannot exceed PO remaining quantity.');
            }

            $lineBase = $this->math->mul($quantity, (string) $sourceLine->unit_price);
            $selectedTotal = $this->math->add($selectedTotal, $lineBase);
            $lineQuantities['purchase_order_line:'.$sourceLine->getKey()] = $quantity;

            $invoiceLines[] = new InvoiceLineData(
                lineNumber: $lineNumber++,
                description: $sourceLine->description ?? 'Purchase item',
                quantity: $quantity,
                unitPrice: (string) $sourceLine->unit_price,
                lineType: InvoiceLineType::Item,
                itemId: (int) $sourceLine->item_id,
                uomId: $sourceLine->uom_id,
                discountAmount: $this->proportionalAmount((string) $sourceLine->discount_amount, $quantity, (string) $sourceLine->ordered_quantity),
                taxAmount: $this->proportionalAmount((string) $sourceLine->tax_amount, $quantity, (string) $sourceLine->ordered_quantity),
                chargeAmount: $this->proportionalAmount((string) $sourceLine->charge_amount, $quantity, (string) $sourceLine->ordered_quantity),
                sourceLineType: 'purchase_order_line',
                sourceLineId: (int) $sourceLine->getKey(),
            );

            $sourceLines[] = new InvoiceSourceLineData(
                tenantId: $data->tenantId,
                sourceType: 'purchase_order',
                sourceId: (int) $order->getKey(),
                sourceLineType: 'purchase_order_line',
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
        $sourceKey = 'purchase_order:'.$order->getKey();
        $sourceTotals[$sourceKey] = [
            'line_total' => $selectedTotal,
            'adjustment_total' => $sourceAdjustmentTotal,
        ];

        $sources[] = new InvoiceSourceData(
            tenantId: $data->tenantId,
            sourceType: 'purchase_order',
            sourceId: (int) $order->getKey(),
            organizationUnitId: $data->organizationUnitId,
            sourceDocumentNumber: $order->purchase_order_number,
            sourceDocumentDate: $order->purchase_order_date->toDateString(),
            sourceSubtotal: (string) $order->subtotal,
            sourceAdjustmentTotal: $sourceAdjustmentTotal,
            sourceGrandTotal: (string) $order->grand_total,
        );

        foreach ($order->adjustments as $adjustment) {
            if ($adjustment instanceof PurchaseHeaderAdjustment && (bool) $adjustment->is_allocatable) {
                $adjustments[] = $this->toInvoiceAdjustment($adjustment, (int) $order->getKey(), 'purchase_order');
            }
        }

        return $lineNumber;
    }

    private function toInvoiceAdjustment(PurchaseHeaderAdjustment $adjustment, int $sourceId, string $sourceType = 'goods_receipt_note'): InvoiceAdjustmentData
    {
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

    private function proportionalAmount(string $amount, string $selectedQuantity, string $sourceQuantity): string
    {
        if ($this->math->isZero($amount) || $this->math->isZero($sourceQuantity)) {
            return '0.000000';
        }

        return $this->math->mul($amount, $this->math->div($selectedQuantity, $sourceQuantity, 12));
    }

    private function refreshGrnInvoiceStatuses(Collection $grns): void
    {
        foreach ($grns as $grn) {
            $grn->load('lines');
            $accepted = $this->math->sum($grn->lines->pluck('accepted_quantity')->all());
            $invoiced = $this->math->sum($grn->lines->pluck('invoiced_quantity')->all());
            if ($this->math->compare($invoiced, $accepted) >= 0) {
                $grn->status = GoodsReceiptNoteStatus::Invoiced;
            } elseif ($this->math->compare($invoiced, '0.000000') > 0) {
                $grn->status = GoodsReceiptNoteStatus::PartiallyInvoiced;
            }
            $grn->save();
        }
    }
}
