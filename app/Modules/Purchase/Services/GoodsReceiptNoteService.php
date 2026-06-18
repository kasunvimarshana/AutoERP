<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Validators\PurchaseValidationService;
use Modules\Tax\Services\TaxDocumentIntegrationService;

final class GoodsReceiptNoteService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseValidationService $validator,
        private readonly PurchaseOrderCalculationService $calculator,
        private readonly PurchaseHeaderAdjustmentService $adjustments,
        private readonly PurchaseOrderQuantityService $orderQuantities,
        private readonly PurchaseInventoryIntegrationService $inventory,
        private readonly PurchaseUomService $uoms,
        private readonly PurchaseNumberService $numbers,
        private readonly TaxDocumentIntegrationService $taxDocuments,
    ) {}

    public function create(CreateGoodsReceiptNoteData $data): GoodsReceiptNote
    {
        $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId, 'warehouse_id');
        if ($data->warehouseLocationId !== null) {
            $this->validator->warehouseLocation(
                $data->tenantId,
                $data->organizationUnitId,
                $data->warehouseId,
                $data->warehouseLocationId,
                'warehouse_location_id',
            );
        }

        $order = $data->purchaseOrderId !== null
            ? PurchaseOrder::query()->with(['lines', 'adjustments'])->findOrFail($data->purchaseOrderId)
            : null;

        if ($order instanceof PurchaseOrder) {
            $this->validator->assertTenantOrg(
                $order->tenant_id !== null ? (int) $order->tenant_id : null,
                $order->organization_unit_id !== null ? (int) $order->organization_unit_id : null,
                $data->tenantId,
                $data->organizationUnitId,
                'purchase_order_id',
                'purchase order',
            );
            $orderStatus = $order->status instanceof PurchaseOrderStatus
                ? $order->status
                : PurchaseOrderStatus::from((string) $order->status);
            if (! in_array($orderStatus, [
                PurchaseOrderStatus::Approved,
                PurchaseOrderStatus::PartiallyReceived,
                PurchaseOrderStatus::Received,
                PurchaseOrderStatus::PartiallyInvoiced,
                PurchaseOrderStatus::Invoiced,
            ], true)) {
                throw new InvalidArgumentException('Goods receipts can only be created from approved purchase orders.');
            }
            if ($data->supplierId !== null && (int) $data->supplierId !== (int) $order->supplier_id) {
                throw new InvalidArgumentException('GRN supplier must match the selected purchase order.');
            }
            if ($data->supplierType !== null && $data->supplierType !== $order->supplier_type) {
                throw new InvalidArgumentException('GRN supplier type must match the selected purchase order.');
            }
        } elseif ($data->supplierId === null) {
            throw new InvalidArgumentException('Standalone Purchase GRNs require a supplier.');
        } elseif ($data->supplierId !== null) {
            $this->validator->supplier($data->tenantId, $data->organizationUnitId, $data->supplierId, 'supplier_id');
        }

        $sourceLines = [];
        $seenSourceLines = [];
        foreach ($data->lines as $index => $line) {
            $this->validateReceiptQuantities($line);

            if ($line->purchaseOrderLineId !== null) {
                if (! $order instanceof PurchaseOrder) {
                    throw new InvalidArgumentException('A GRN source purchase order is required when a PO line is selected.');
                }

                $poLine = $order->lines->firstWhere('id', $line->purchaseOrderLineId);
                if (! $poLine instanceof PurchaseOrderLine) {
                    throw new InvalidArgumentException('GRN source line must belong to the selected purchase order.');
                }

                $sourceKey = (int) $poLine->getKey();
                if (isset($seenSourceLines[$sourceKey])) {
                    throw new InvalidArgumentException('Duplicate GRN source purchase order line.');
                }
                $seenSourceLines[$sourceKey] = true;

                $this->assertSourceLineMatchesPayload($poLine, $line);
                $this->validator->assertReceiptWithinOrder($poLine, $line->receivedQuantity);
                $sourceLines[$sourceKey] = $poLine;

                continue;
            }

            if ($order instanceof PurchaseOrder) {
                throw new InvalidArgumentException('PO-based GRNs require every line to reference a purchase order line.');
            }

            $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId, "lines.{$index}.item_id");
            $uomId = $line->orderedUomId ?? $line->uomId;
            if ($uomId === null) {
                throw new InvalidArgumentException('GRN line UOM is required.');
            }
            $uomField = $line->orderedUomId !== null
                ? "lines.{$index}.ordered_uom_id"
                : "lines.{$index}.uom_id";
            $this->validator->uom($data->tenantId, $data->organizationUnitId, $uomId, $uomField);
            $this->validator->assertNonNegative($line->unitPrice, 'GRN line unit price cannot be negative.');
            $this->uoms->resolveLineUom($data->tenantId, $item, $uomId, $line->acceptedQuantity);
        }

        return DB::transaction(function () use ($data, $order, $sourceLines): GoodsReceiptNote {
            $calculation = $this->calculator->calculate($this->receiptLinesAsOrderLines($data, $sourceLines), []);
            $grn = GoodsReceiptNote::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'purchase_order_id' => $data->purchaseOrderId,
                'supplier_type' => $data->supplierType ?? $order?->supplier_type,
                'supplier_id' => $data->supplierId ?? $order?->supplier_id,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'grn_number' => $data->grnNumber ?? $this->numbers->next($data->tenantId, 'GRN', 'goods_receipt_notes', 'grn_number'),
                'received_date' => $data->receivedDate,
                'status' => GoodsReceiptNoteStatus::Draft,
                'subtotal' => $calculation->subtotal,
                'discount_total' => $calculation->discountTotal,
                'tax_total' => $calculation->taxTotal,
                'charge_total' => $calculation->chargeTotal,
                'grand_total' => $calculation->grandTotal,
                'notes' => $data->notes,
                'received_by' => $data->receivedBy,
            ]);

            foreach ($data->lines as $index => $line) {
                $poLine = $line->purchaseOrderLineId !== null
                    ? ($sourceLines[$line->purchaseOrderLineId] ?? PurchaseOrderLine::query()->find($line->purchaseOrderLineId))
                    : null;
                $itemId = $poLine instanceof PurchaseOrderLine ? (int) $poLine->item_id : $line->itemId;
                $variantId = $poLine instanceof PurchaseOrderLine ? $poLine->item_variant_id : $line->itemVariantId;
                $item = Item::query()->findOrFail($itemId);
                $uomId = $poLine instanceof PurchaseOrderLine
                    ? (int) ($poLine->ordered_uom_id ?: $poLine->uom_id)
                    : ($line->orderedUomId ?? $line->uomId);
                $uom = $this->uoms->resolveLineUom($data->tenantId, $item, (int) $uomId, $line->acceptedQuantity);
                $unitPrice = $line->unitPrice;
                $discountAmount = $line->discountAmount;
                $taxAmount = $line->taxAmount;
                $chargeAmount = $line->chargeAmount;
                if ($poLine instanceof PurchaseOrderLine) {
                    $unitPrice = (string) $poLine->unit_price;
                    [$discountAmount, $taxAmount, $chargeAmount] = $this->sourceLineFinancialAmounts($poLine, $line->acceptedQuantity);
                }
                $amounts = $this->calculator->lineAmounts((object) [
                    'orderedQuantity' => $line->acceptedQuantity,
                    'unitPrice' => $unitPrice,
                    'discountAmount' => $discountAmount,
                    'taxAmount' => $taxAmount,
                    'chargeAmount' => $chargeAmount,
                ]);
                $grn->lines()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'purchase_order_line_id' => $line->purchaseOrderLineId,
                    'item_id' => $itemId,
                    'item_variant_id' => $variantId,
                    'description' => $line->description ?? $poLine?->description,
                    'uom_id' => $uom['ordered_uom_id'],
                    'ordered_uom_id' => $uom['ordered_uom_id'],
                    'base_uom_id' => $uom['base_uom_id'],
                    'uom_conversion_factor' => $uom['conversion_factor'],
                    'ordered_quantity' => $this->math->normalize($poLine instanceof PurchaseOrderLine ? (string) $poLine->ordered_quantity : $line->orderedQuantity),
                    'received_quantity' => $this->math->normalize($line->receivedQuantity),
                    'base_received_quantity' => $line->baseReceivedQuantity ?? $this->math->mul($line->receivedQuantity, $uom['conversion_factor']),
                    'accepted_quantity' => $this->math->normalize($line->acceptedQuantity),
                    'base_accepted_quantity' => $line->baseAcceptedQuantity ?? $uom['base_quantity'],
                    'rejected_quantity' => $this->math->normalize($line->rejectedQuantity),
                    'remaining_quantity' => $this->math->normalize($line->acceptedQuantity),
                    'unit_price' => $this->math->normalize($unitPrice),
                    'line_subtotal' => $amounts['subtotal'],
                    'discount_amount' => $this->math->normalize($discountAmount),
                    'tax_amount' => $this->math->normalize($taxAmount),
                    'charge_amount' => $this->math->normalize($chargeAmount),
                    'line_total' => $calculation->lineTotals[$index],
                    'status' => GoodsReceiptNoteLineStatus::Open,
                ]);
            }

            $this->copyOrderAdjustments($order, $grn);

            return $grn->load(['lines', 'adjustments']);
        });
    }

    public function post(GoodsReceiptNote $grn, ?int $postedBy = null): GoodsReceiptNote
    {
        return DB::transaction(function () use ($grn, $postedBy): GoodsReceiptNote {
            $locked = GoodsReceiptNote::query()
                ->with('lines.purchaseOrderLine')
                ->lockForUpdate()
                ->findOrFail($grn->getKey());

            if ($locked->status === GoodsReceiptNoteStatus::Posted) {
                return $locked->refresh()->load(['lines', 'adjustments']);
            }

            if ($locked->status !== GoodsReceiptNoteStatus::Draft) {
                throw new InvalidArgumentException('Only draft GRNs can be posted.');
            }

            foreach ($locked->lines as $line) {
                if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
                    $sourceLine = PurchaseOrderLine::query()
                        ->lockForUpdate()
                        ->findOrFail((int) $line->purchase_order_line_id);
                    $this->validator->assertReceiptWithinOrder($sourceLine, (string) $line->received_quantity);
                    $line->setRelation('purchaseOrderLine', $sourceLine);
                }

                $movement = $this->inventory->receipt($locked, $line, $postedBy);
                if ($movement !== null) {
                    $line->inventory_movement_id = $movement->getKey();
                }
                $line->status = GoodsReceiptNoteLineStatus::Posted;
                $line->save();

                if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
                    $this->orderQuantities->applyReceived($line->purchaseOrderLine, (string) $line->accepted_quantity);
                }
            }

            $locked->status = GoodsReceiptNoteStatus::Posted;
            $locked->posted_by = $postedBy;
            $locked->posted_at = now();
            $locked->save();
            $this->taxDocuments->postGoodsReceiptNote($locked->refresh()->load('lines'));

            return $locked->refresh()->load(['lines', 'adjustments']);
        });
    }

    public function reverse(GoodsReceiptNote $grn, ?int $reversedBy = null): GoodsReceiptNote
    {
        return DB::transaction(function () use ($grn, $reversedBy): GoodsReceiptNote {
            $locked = GoodsReceiptNote::query()
                ->with('lines.purchaseOrderLine')
                ->lockForUpdate()
                ->findOrFail($grn->getKey());

            if ($locked->status === GoodsReceiptNoteStatus::Reversed) {
                return $locked->refresh()->load(['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments']);
            }

            if ($locked->status !== GoodsReceiptNoteStatus::Posted) {
                throw new InvalidArgumentException('Only posted GRNs can be reversed.');
            }

            foreach ($locked->lines as $line) {
                if ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0
                    || $this->math->compare((string) $line->returned_quantity, '0.000000') > 0) {
                    throw new InvalidArgumentException('GRNs with invoiced or returned lines cannot be reversed.');
                }

                $this->inventory->reverseReceipt($locked, $line, $reversedBy);
                if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
                    $this->orderQuantities->reverseReceived($line->purchaseOrderLine, (string) $line->accepted_quantity);
                }
                $line->status = GoodsReceiptNoteLineStatus::Reversed;
                $line->save();
            }

            $this->taxDocuments->reverseGoodsReceiptNote($locked);

            $locked->status = GoodsReceiptNoteStatus::Reversed;
            $locked->reversed_by = $reversedBy;
            $locked->reversed_at = now();
            $locked->save();

            return $locked->refresh()->load(['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments']);
        });
    }

    private function copyOrderAdjustments(?PurchaseOrder $order, GoodsReceiptNote $grn): void
    {
        if (! $order instanceof PurchaseOrder) {
            return;
        }

        $ratio = $this->math->isZero((string) $order->subtotal)
            ? '0.000000'
            : $this->math->div((string) $grn->subtotal, (string) $order->subtotal, 12);

        foreach ($order->adjustments as $adjustment) {
            if ($adjustment instanceof PurchaseHeaderAdjustment) {
                $this->adjustments->cloneProportionally($adjustment, 'goods_receipt_note', (int) $grn->getKey(), $ratio);
            }
        }
    }

    private function validateReceiptQuantities(GoodsReceiptNoteLineData $line): void
    {
        $this->validator->assertPositiveQuantity($line->receivedQuantity);
        $this->validator->assertNonNegative($line->acceptedQuantity, 'Accepted quantity cannot be negative.');
        $this->validator->assertNonNegative($line->rejectedQuantity, 'Rejected quantity cannot be negative.');

        $total = $this->math->add($line->acceptedQuantity, $line->rejectedQuantity);
        if ($this->math->compare($total, $line->receivedQuantity) !== 0) {
            throw new InvalidArgumentException('Accepted and rejected quantities must equal received quantity.');
        }
    }

    private function assertSourceLineMatchesPayload(PurchaseOrderLine $poLine, GoodsReceiptNoteLineData $line): void
    {
        if ((int) $poLine->item_id !== $line->itemId) {
            throw new InvalidArgumentException('GRN line item must match the source purchase order line.');
        }

        if ($line->itemVariantId !== null && (int) ($poLine->item_variant_id ?? 0) !== $line->itemVariantId) {
            throw new InvalidArgumentException('GRN line item variant must match the source purchase order line.');
        }

        $sourceUomId = (int) ($poLine->ordered_uom_id ?: $poLine->uom_id);
        foreach ([$line->orderedUomId, $line->uomId] as $uomId) {
            if ($uomId !== null && $uomId !== $sourceUomId) {
                throw new InvalidArgumentException('GRN line UOM must match the source purchase order line.');
            }
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function sourceLineFinancialAmounts(PurchaseOrderLine $poLine, string $acceptedQuantity): array
    {
        $ratio = $this->math->isZero((string) $poLine->ordered_quantity)
            ? '0.000000'
            : $this->math->div($acceptedQuantity, (string) $poLine->ordered_quantity, 12);

        return [
            $this->math->mul((string) $poLine->discount_amount, $ratio),
            $this->math->mul((string) $poLine->tax_amount, $ratio),
            $this->math->mul((string) $poLine->charge_amount, $ratio),
        ];
    }

    /**
     * @param  array<int, PurchaseOrderLine>  $sourceLines
     * @return list<object>
     */
    private function receiptLinesAsOrderLines(CreateGoodsReceiptNoteData $data, array $sourceLines): array
    {
        return array_map(
            function (GoodsReceiptNoteLineData $line) use ($sourceLines): object {
                $poLine = $line->purchaseOrderLineId !== null ? ($sourceLines[$line->purchaseOrderLineId] ?? null) : null;
                $unitPrice = $poLine instanceof PurchaseOrderLine ? (string) $poLine->unit_price : $line->unitPrice;
                $discountAmount = $line->discountAmount;
                $taxAmount = $line->taxAmount;
                $chargeAmount = $line->chargeAmount;

                if ($poLine instanceof PurchaseOrderLine) {
                    [$discountAmount, $taxAmount, $chargeAmount] = $this->sourceLineFinancialAmounts($poLine, $line->acceptedQuantity);
                }

                return (object) [
                    'orderedQuantity' => $line->acceptedQuantity,
                    'unitPrice' => $unitPrice,
                    'discountAmount' => $discountAmount,
                    'taxAmount' => $taxAmount,
                    'chargeAmount' => $chargeAmount,
                ];
            },
            $data->lines,
        );
    }
}
