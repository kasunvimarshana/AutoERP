<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
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
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\GoodsReceiptNoteLine;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseInvoiceLink;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;

final class PurchaseInvoiceDtoFactory
{
    private const GOODS_RECEIPT = 'goods_receipt_note';

    private const GOODS_RECEIPT_LINE = 'goods_receipt_note_line';

    private const PURCHASE_ORDER = 'purchase_order';

    private const PURCHASE_ORDER_LINE = 'purchase_order_line';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseProcurementBalanceService $balances,
        private readonly PurchaseDocumentLockService $locks,
    ) {}

    public function prepare(
        CreatePurchaseInvoiceData $data,
        bool $lockSources = false,
    ): PreparedPurchaseInvoiceData {
        $sources = [];
        $sourceLines = [];
        $invoiceLines = [];
        $adjustments = $this->assertInvoiceAdjustments($data->adjustments);
        $sourceTotals = [];
        $lineQuantities = [];
        $lineNumber = 1;
        $goodsReceipts = collect();
        $resolvedSupplierType = $data->supplierType;
        $resolvedSupplierId = $data->supplierId;
        $seenSources = [];
        $lineage = [
            'source_types_by_po_line' => [],
            'requested_by_po_line' => [],
        ];
        $lockedSources = $lockSources ? $this->lockInvoiceSources($data->sources) : null;

        foreach ($data->sources as $index => $source) {
            if (! $source instanceof PurchaseInvoiceSourceData) {
                throw new InvalidArgumentException('Purchase supplier invoice sources are invalid.');
            }

            $sourceKey = $source->sourceType.':'.$source->sourceId;
            if (isset($seenSources[$sourceKey])) {
                throw new InvalidArgumentException('Duplicate purchase invoice source document.');
            }
            $seenSources[$sourceKey] = true;

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
                    $lineage,
                    $lockSources,
                    $resolvedSupplierType,
                    $resolvedSupplierId,
                    "sources.{$index}.source_id",
                    $lockedSources,
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
                $lineage,
                $goodsReceipts,
                $lockSources,
                $resolvedSupplierType,
                $resolvedSupplierId,
                "sources.{$index}.source_id",
                $lockedSources,
            );
        }

        $lineNumber = $this->appendDirectLines($data->directLines, $lineNumber, $invoiceLines);

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
                status: $data->status,
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

    /**
     * @param  list<PurchaseInvoiceSourceData>  $sources
     * @return array{
     *     purchase_orders: array<int, PurchaseOrder>,
     *     goods_receipts: array<int, GoodsReceiptNote>
     * }
     */
    private function lockInvoiceSources(array $sources): array
    {
        $purchaseOrderIds = [];
        $purchaseOrderLineIds = [];
        $goodsReceiptIds = [];
        $goodsReceiptLineIds = [];
        $sourcePairs = [];

        foreach ($sources as $source) {
            if (! $source instanceof PurchaseInvoiceSourceData) {
                throw new InvalidArgumentException('Purchase supplier invoice sources are invalid.');
            }

            if ($source->sourceType === self::PURCHASE_ORDER) {
                $purchaseOrderIds[] = $source->sourceId;
                $sourcePairs[] = [self::PURCHASE_ORDER, $source->sourceId];

                continue;
            }

            if ($source->sourceType === self::GOODS_RECEIPT) {
                $goodsReceiptIds[] = $source->sourceId;
                $sourcePairs[] = [self::GOODS_RECEIPT, $source->sourceId];

                continue;
            }

            throw new InvalidArgumentException(
                'Purchase supplier invoices require purchase order or goods receipt note sources.',
            );
        }

        $orderSnapshots = $purchaseOrderIds === []
            ? collect()
            : PurchaseOrder::query()
                ->with('lines')
                ->whereIn('id', array_values(array_unique($purchaseOrderIds)))
                ->get();
        foreach ($orderSnapshots as $order) {
            if (! $order instanceof PurchaseOrder) {
                continue;
            }
            foreach ($order->lines as $line) {
                if ($line instanceof PurchaseOrderLine) {
                    $purchaseOrderLineIds[] = (int) $line->getKey();
                }
            }
        }

        $receiptSnapshots = $goodsReceiptIds === []
            ? collect()
            : GoodsReceiptNote::query()
                ->with('lines.purchaseOrderLine')
                ->whereIn('id', array_values(array_unique($goodsReceiptIds)))
                ->get();
        foreach ($receiptSnapshots as $receipt) {
            if (! $receipt instanceof GoodsReceiptNote) {
                continue;
            }
            foreach ($receipt->lines as $line) {
                if (! $line instanceof GoodsReceiptNoteLine) {
                    continue;
                }
                $goodsReceiptLineIds[] = (int) $line->getKey();
                if ($line->purchase_order_line_id !== null) {
                    $purchaseOrderLineIds[] = (int) $line->purchase_order_line_id;
                }
                if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
                    $purchaseOrderIds[] = (int) $line->purchaseOrderLine->purchase_order_id;
                }
            }
        }

        $lockedOrders = $this->locks->purchaseOrders($purchaseOrderIds)
            ->keyBy(fn (PurchaseOrder $order): int => (int) $order->getKey());
        $lockedOrderLines = $this->locks->purchaseOrderLines($purchaseOrderLineIds)
            ->keyBy(fn (PurchaseOrderLine $line): int => (int) $line->getKey());
        $lockedReceipts = $this->locks->goodsReceipts($goodsReceiptIds)
            ->keyBy(fn (GoodsReceiptNote $receipt): int => (int) $receipt->getKey());
        $lockedReceiptLines = $this->locks->goodsReceiptLines($goodsReceiptLineIds)
            ->keyBy(fn (GoodsReceiptNoteLine $line): int => (int) $line->getKey());

        if ($sourcePairs !== []) {
            PurchaseInvoiceLink::query()
                ->where(function ($query) use ($sourcePairs): void {
                    foreach ($sourcePairs as [$sourceType, $sourceId]) {
                        $query->orWhere(function ($scope) use ($sourceType, $sourceId): void {
                            $scope->where('source_type', $sourceType)
                                ->where('source_id', $sourceId);
                        });
                    }
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
        }

        $adjustments = ($purchaseOrderIds === [] && $goodsReceiptIds === [])
            ? collect()
            : PurchaseHeaderAdjustment::query()
                ->where(function ($query) use ($purchaseOrderIds, $goodsReceiptIds): void {
                    if ($purchaseOrderIds !== []) {
                        $query->orWhere(function ($scope) use ($purchaseOrderIds): void {
                            $scope->where('source_type', self::PURCHASE_ORDER)
                                ->whereIn('source_id', array_values(array_unique($purchaseOrderIds)));
                        });
                    }
                    if ($goodsReceiptIds !== []) {
                        $query->orWhere(function ($scope) use ($goodsReceiptIds): void {
                            $scope->where('source_type', self::GOODS_RECEIPT)
                                ->whereIn('source_id', array_values(array_unique($goodsReceiptIds)));
                        });
                    }
                })
                ->orderBy('source_type')
                ->orderBy('source_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->groupBy(fn (PurchaseHeaderAdjustment $adjustment): string => $adjustment->source_type.':'.$adjustment->source_id);

        foreach ($lockedOrderLines as $line) {
            $order = $lockedOrders->get((int) $line->purchase_order_id);
            if ($order instanceof PurchaseOrder) {
                $line->setRelation('order', $order);
            }
        }
        foreach ($lockedOrders as $order) {
            $order->setRelation('lines', $lockedOrderLines
                ->where('purchase_order_id', (int) $order->getKey())
                ->values());
            $order->setRelation('adjustments', $adjustments->get(self::PURCHASE_ORDER.':'.$order->getKey(), collect())->values());
        }

        foreach ($lockedReceiptLines as $line) {
            if ($line->purchase_order_line_id !== null) {
                $orderLine = $lockedOrderLines->get((int) $line->purchase_order_line_id);
                if ($orderLine instanceof PurchaseOrderLine) {
                    $line->setRelation('purchaseOrderLine', $orderLine);
                }
            }
        }
        foreach ($lockedReceipts as $receipt) {
            $receipt->setRelation('lines', $lockedReceiptLines
                ->where('goods_receipt_note_id', (int) $receipt->getKey())
                ->values());
            $receipt->setRelation('adjustments', $adjustments->get(self::GOODS_RECEIPT.':'.$receipt->getKey(), collect())->values());
        }

        return [
            'purchase_orders' => $lockedOrders->all(),
            'goods_receipts' => $lockedReceipts->all(),
        ];
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
        array &$lineage,
        Collection $goodsReceipts,
        bool $lockSources,
        ?string &$resolvedSupplierType,
        ?int &$resolvedSupplierId,
        string $field,
        ?array $lockedSources,
    ): int {
        $grn = $lockedSources === null
            ? GoodsReceiptNote::query()->with(['lines.purchaseOrderLine.order', 'adjustments'])->find($source->sourceId)
            : ($lockedSources['goods_receipts'][$source->sourceId] ?? null);
        if (! $grn instanceof GoodsReceiptNote) {
            $this->invalidReference($field, 'goods receipt');
        }

        $this->assertSourceScope(
            $grn->tenant_id !== null ? (int) $grn->tenant_id : null,
            $grn->organization_unit_id !== null ? (int) $grn->organization_unit_id : null,
            $data,
            $field,
            'goods receipt',
        );
        if (! $this->isInvoiceableGoodsReceiptStatus($grn)) {
            throw new InvalidArgumentException('Purchase invoices can only use posted goods receipts.');
        }
        [$resolvedSupplierType, $resolvedSupplierId] = $this->resolveSupplier(
            $resolvedSupplierType,
            $resolvedSupplierId,
            $grn->supplier_type,
            $grn->supplier_id,
            $data->supplierId !== null ? 'supplier_id' : $field,
        );

        $goodsReceipts->push($grn);
        $selectedTotal = '0.000000';
        $selectedLines = 0;
        $requestedLineIds = array_map('intval', array_keys($source->lineQuantities));
        $seenRequestedLineIds = [];

        foreach ($grn->lines as $sourceLine) {
            if ($sourceLine->purchaseOrderLine instanceof PurchaseOrderLine
                && $sourceLine->purchaseOrderLine->relationLoaded('order')
                && in_array($sourceLine->purchaseOrderLine->order?->status, [PurchaseOrderStatus::Closed, PurchaseOrderStatus::Cancelled], true)) {
                throw new InvalidArgumentException('Purchase invoices cannot be created after the source purchase order is closed or cancelled.');
            }

            $remainingInvoiceable = $this->balances->remainingInvoiceableForGoodsReceiptLine($sourceLine);
            if ($this->math->isNegative($remainingInvoiceable)) {
                throw new InvalidArgumentException('GRN invoiceable quantity is negative.');
            }

            if (array_key_exists((int) $sourceLine->getKey(), $source->lineQuantities)) {
                $seenRequestedLineIds[] = (int) $sourceLine->getKey();
            }
            $quantity = $source->lineQuantities === []
                ? $remainingInvoiceable
                : ($source->lineQuantities[(int) $sourceLine->getKey()] ?? '0.000000');
            $quantity = $this->math->normalize($quantity);
            if ($this->math->isZero($quantity)) {
                continue;
            }
            if ($this->math->compare($quantity, $remainingInvoiceable) > 0) {
                throw new InvalidArgumentException(
                    'Purchase invoice quantity cannot exceed GRN remaining procurement quantity.',
                );
            }
            if ($sourceLine->purchaseOrderLine instanceof PurchaseOrderLine) {
                $this->trackProcurementLineage(
                    $lineage,
                    (int) $sourceLine->purchaseOrderLine->getKey(),
                    self::GOODS_RECEIPT_LINE,
                    $quantity,
                    $this->balances->remainingInvoiceableForPurchaseOrderLine($sourceLine->purchaseOrderLine),
                );
            }
            $selectedLines++;

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
                metadata: [
                    'tax_group_id' => $sourceLine->tax_group_id,
                    'purchase_order_line_id' => $sourceLine->purchase_order_line_id,
                ],
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

        if (array_diff($requestedLineIds, $seenRequestedLineIds) !== []) {
            throw new InvalidArgumentException('Purchase invoice source line does not belong to the selected goods receipt.');
        }
        if ($selectedLines === 0) {
            throw new InvalidArgumentException('Purchase invoice source has no invoiceable lines.');
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

    /**
     * @param  list<InvoiceLineData>  $directLines
     * @param  list<InvoiceLineData>  $invoiceLines
     */
    private function appendDirectLines(array $directLines, int $lineNumber, array &$invoiceLines): int
    {
        foreach ($directLines as $line) {
            if (! $line instanceof InvoiceLineData) {
                throw new InvalidArgumentException('Purchase supplier invoice direct lines are invalid.');
            }

            $invoiceLines[] = new InvoiceLineData(
                lineNumber: $lineNumber++,
                description: $line->description,
                quantity: $line->quantity,
                unitPrice: $line->unitPrice,
                lineType: $line->lineType,
                itemId: $line->itemId,
                uomId: $line->uomId,
                discountAmount: $line->discountAmount,
                taxAmount: $line->taxAmount,
                chargeAmount: $line->chargeAmount,
                lineTotal: $line->lineTotal,
                sourceLineType: $line->sourceLineType,
                sourceLineId: $line->sourceLineId,
                metadata: $line->metadata,
            );
        }

        return $lineNumber;
    }

    /**
     * @param  list<InvoiceAdjustmentData>  $adjustments
     * @return list<InvoiceAdjustmentData>
     */
    private function assertInvoiceAdjustments(array $adjustments): array
    {
        foreach ($adjustments as $adjustment) {
            if (! $adjustment instanceof InvoiceAdjustmentData) {
                throw new InvalidArgumentException('Purchase supplier invoice adjustments are invalid.');
            }
        }

        return $adjustments;
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
        array &$lineage,
        bool $lockSources,
        ?string &$resolvedSupplierType,
        ?int &$resolvedSupplierId,
        string $field,
        ?array $lockedSources,
    ): int {
        $order = $lockedSources === null
            ? PurchaseOrder::query()->with(['lines', 'adjustments'])->find($source->sourceId)
            : ($lockedSources['purchase_orders'][$source->sourceId] ?? null);
        if (! $order instanceof PurchaseOrder) {
            $this->invalidReference($field, 'purchase order');
        }

        $this->assertSourceScope(
            $order->tenant_id !== null ? (int) $order->tenant_id : null,
            $order->organization_unit_id !== null ? (int) $order->organization_unit_id : null,
            $data,
            $field,
            'purchase order',
        );
        if (! $this->isInvoiceablePurchaseOrderStatus($order)) {
            throw new InvalidArgumentException('Purchase invoices can only use approved purchase orders.');
        }
        [$resolvedSupplierType, $resolvedSupplierId] = $this->resolveSupplier(
            $resolvedSupplierType,
            $resolvedSupplierId,
            $order->supplier_type,
            $order->supplier_id,
            $data->supplierId !== null ? 'supplier_id' : $field,
        );

        $selectedTotal = '0.000000';
        $selectedLines = 0;
        $requestedLineIds = array_map('intval', array_keys($source->lineQuantities));
        $seenRequestedLineIds = [];
        foreach ($order->lines as $sourceLine) {
            /** @var PurchaseOrderLine $sourceLine */
            $remaining = $this->balances->remainingInvoiceableForPurchaseOrderLine($sourceLine);
            if ($this->math->isNegative($remaining)) {
                throw new InvalidArgumentException('PO invoiceable quantity is negative.');
            }
            if (array_key_exists((int) $sourceLine->getKey(), $source->lineQuantities)) {
                $seenRequestedLineIds[] = (int) $sourceLine->getKey();
            }
            $quantity = $source->lineQuantities === []
                ? $remaining
                : ($source->lineQuantities[(int) $sourceLine->getKey()] ?? '0.000000');
            $quantity = $this->math->normalize($quantity);

            if ($this->math->isZero($quantity)) {
                continue;
            }
            if ($this->math->compare($quantity, $remaining) > 0) {
                throw new InvalidArgumentException(
                    'Purchase invoice quantity cannot exceed PO remaining quantity.',
                );
            }
            $this->trackProcurementLineage(
                $lineage,
                (int) $sourceLine->getKey(),
                self::PURCHASE_ORDER_LINE,
                $quantity,
                $remaining,
            );
            $selectedLines++;

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
                metadata: [
                    'tax_group_id' => $sourceLine->tax_group_id,
                ],
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

        if (array_diff($requestedLineIds, $seenRequestedLineIds) !== []) {
            throw new InvalidArgumentException('Purchase invoice source line does not belong to the selected purchase order.');
        }
        if ($selectedLines === 0) {
            throw new InvalidArgumentException('Purchase invoice source has no invoiceable lines.');
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
        ?int $sourceTenantId,
        ?int $sourceOrganizationUnitId,
        CreatePurchaseInvoiceData $data,
        string $field,
        string $label,
    ): void {
        if ($sourceTenantId !== $data->tenantId) {
            $this->invalidReference($field, $label);
        }

        if ($sourceOrganizationUnitId !== $data->organizationUnitId) {
            $this->invalidReference($field, $label, "The selected {$label} is not available for this organization unit.");
        }
    }

    private function invalidReference(string $field, string $label, ?string $message = null): never
    {
        throw ValidationException::withMessages([
            $field => [$message ?? "The selected {$label} is not available."],
        ]);
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
        $amount = (string) $adjustment->amount;

        return new InvoiceAdjustmentData(
            name: (string) $adjustment->name,
            adjustmentType: $this->toInvoiceAdjustmentType($adjustment),
            effect: AdjustmentEffect::from($adjustment->effect->value),
            amount: $amount,
            sourceAdjustmentType: 'purchase_header_adjustment',
            sourceAdjustmentId: (int) $adjustment->getKey(),
            sourceType: $sourceType,
            sourceId: $sourceId,
            calculationType: $adjustment->calculation_type->value,
            rate: (string) $adjustment->rate,
            sourceAmount: $amount,
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
            $amount = (string) $adjustment->amount;
            $total = $adjustment->effect->value === 'increase'
                ? $this->math->add($total, $amount)
                : $this->math->sub($total, $amount);
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
     * @param  array{source_types_by_po_line: array<int, array<string, bool>>, requested_by_po_line: array<int, string>}  $lineage
     */
    private function trackProcurementLineage(
        array &$lineage,
        int $purchaseOrderLineId,
        string $sourceLineType,
        string $quantity,
        string $remaining,
    ): void {
        $existingTypes = $lineage['source_types_by_po_line'][$purchaseOrderLineId] ?? [];
        if (($sourceLineType === self::PURCHASE_ORDER_LINE && isset($existingTypes[self::GOODS_RECEIPT_LINE]))
            || ($sourceLineType === self::GOODS_RECEIPT_LINE && isset($existingTypes[self::PURCHASE_ORDER_LINE]))
        ) {
            throw new InvalidArgumentException(
                'Purchase invoice cannot mix a purchase order line with goods receipt lines derived from the same purchase order line.',
            );
        }

        $lineage['source_types_by_po_line'][$purchaseOrderLineId][$sourceLineType] = true;
        $requested = $this->math->add(
            $lineage['requested_by_po_line'][$purchaseOrderLineId] ?? '0.000000',
            $quantity,
        );
        if ($this->math->compare($requested, $remaining) > 0) {
            throw new InvalidArgumentException(
                'Purchase invoice quantity cannot exceed cumulative procurement remaining quantity.',
            );
        }
        $lineage['requested_by_po_line'][$purchaseOrderLineId] = $requested;
    }

    private function isInvoiceableGoodsReceiptStatus(GoodsReceiptNote $grn): bool
    {
        $status = $grn->status instanceof GoodsReceiptNoteStatus
            ? $grn->status
            : GoodsReceiptNoteStatus::from((string) $grn->status);

        return in_array($status, [
            GoodsReceiptNoteStatus::Posted,
        ], true);
    }

    private function isInvoiceablePurchaseOrderStatus(PurchaseOrder $order): bool
    {
        $status = $order->status instanceof PurchaseOrderStatus
            ? $order->status
            : PurchaseOrderStatus::from((string) $order->status);

        return in_array($status, [
            PurchaseOrderStatus::Approved,
        ], true);
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function resolveSupplier(
        ?string $selectedType,
        ?int $selectedId,
        mixed $sourceType,
        mixed $sourceId,
        string $field,
    ): array {
        $resolvedType = trim((string) $sourceType);
        $resolvedId = is_numeric($sourceId) ? (int) $sourceId : null;

        if ($resolvedType === '' || $resolvedId === null || $resolvedId < 1) {
            throw new InvalidArgumentException('Purchase invoice source requires a supplier.');
        }

        if (($selectedType !== null && $selectedType !== $resolvedType)
            || ($selectedId !== null && $selectedId !== $resolvedId)
        ) {
            $this->invalidReference($field, 'supplier', 'All purchase invoice sources must belong to the selected supplier.');
        }

        return [$resolvedType, $resolvedId];
    }
}
