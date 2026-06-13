<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
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
        $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);

        $order = $data->purchaseOrderId !== null
            ? PurchaseOrder::query()->with(['lines', 'adjustments'])->findOrFail($data->purchaseOrderId)
            : null;

        if ($order instanceof PurchaseOrder) {
            $this->validator->assertTenantOrg((int) $order->tenant_id, $order->organization_unit_id, $data->tenantId, $data->organizationUnitId);
        }

        foreach ($data->lines as $line) {
            $this->validator->assertPositiveQuantity($line->receivedQuantity);
            $this->validator->assertPositiveQuantity($line->acceptedQuantity);
            if ($this->math->compare($line->acceptedQuantity, $line->receivedQuantity) > 0) {
                throw new InvalidArgumentException('Accepted quantity cannot exceed received quantity.');
            }
            $item = $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId);
            $uomId = $line->orderedUomId ?? $line->uomId;
            if ($line->purchaseOrderLineId !== null) {
                $poLine = PurchaseOrderLine::query()->with('order')->findOrFail($line->purchaseOrderLineId);
                $this->validator->assertTenantOrg((int) $poLine->tenant_id, $poLine->organization_unit_id, $data->tenantId, $data->organizationUnitId);
                if ($order instanceof PurchaseOrder && (int) $poLine->purchase_order_id !== (int) $order->getKey()) {
                    throw new InvalidArgumentException('GRN source line must belong to the selected purchase order.');
                }
                $this->validator->assertReceiptWithinOrder($poLine, $line->acceptedQuantity);
                $uomId ??= (int) ($poLine->ordered_uom_id ?: $poLine->uom_id);
            }
            if ($uomId === null) {
                throw new InvalidArgumentException('GRN line UOM is required.');
            }
            $this->validator->uom($data->tenantId, $data->organizationUnitId, $uomId);
            $this->uoms->resolveLineUom($data->tenantId, $item, $uomId, $line->acceptedQuantity);
        }

        return DB::transaction(function () use ($data, $order): GoodsReceiptNote {
            $calculation = $this->calculator->calculate($this->receiptLinesAsOrderLines($data), []);
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
                    ? PurchaseOrderLine::query()->find($line->purchaseOrderLineId)
                    : null;
                $item = Item::query()->findOrFail($line->itemId);
                $uomId = $line->orderedUomId ?? $line->uomId ?? ($poLine?->ordered_uom_id ?: $poLine?->uom_id);
                $uom = $this->uoms->resolveLineUom($data->tenantId, $item, (int) $uomId, $line->acceptedQuantity);
                $discountAmount = $line->discountAmount;
                $taxAmount = $line->taxAmount;
                $chargeAmount = $line->chargeAmount;
                if ($poLine instanceof PurchaseOrderLine) {
                    $ratio = $this->math->isZero((string) $poLine->ordered_quantity)
                        ? '0.000000'
                        : $this->math->div($line->acceptedQuantity, (string) $poLine->ordered_quantity, 12);
                    $discountAmount = $line->discountAmount === '0.000000' ? $this->math->mul((string) $poLine->discount_amount, $ratio) : $line->discountAmount;
                    $taxAmount = $line->taxAmount === '0.000000' ? $this->math->mul((string) $poLine->tax_amount, $ratio) : $line->taxAmount;
                    $chargeAmount = $line->chargeAmount === '0.000000' ? $this->math->mul((string) $poLine->charge_amount, $ratio) : $line->chargeAmount;
                }
                $amounts = $this->calculator->lineAmounts((object) [
                    'orderedQuantity' => $line->acceptedQuantity,
                    'unitPrice' => $line->unitPrice,
                    'discountAmount' => $discountAmount,
                    'taxAmount' => $taxAmount,
                    'chargeAmount' => $chargeAmount,
                ]);
                $grn->lines()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'purchase_order_line_id' => $line->purchaseOrderLineId,
                    'item_id' => $line->itemId,
                    'item_variant_id' => $line->itemVariantId,
                    'description' => $line->description,
                    'uom_id' => $uom['ordered_uom_id'],
                    'ordered_uom_id' => $uom['ordered_uom_id'],
                    'base_uom_id' => $uom['base_uom_id'],
                    'uom_conversion_factor' => $uom['conversion_factor'],
                    'ordered_quantity' => $this->math->normalize($line->orderedQuantity === '0.000000' && $poLine !== null ? (string) $poLine->ordered_quantity : $line->orderedQuantity),
                    'received_quantity' => $this->math->normalize($line->receivedQuantity),
                    'base_received_quantity' => $line->baseReceivedQuantity ?? $this->math->mul($line->receivedQuantity, $uom['conversion_factor']),
                    'accepted_quantity' => $this->math->normalize($line->acceptedQuantity),
                    'base_accepted_quantity' => $line->baseAcceptedQuantity ?? $uom['base_quantity'],
                    'rejected_quantity' => $this->math->normalize($line->rejectedQuantity),
                    'remaining_quantity' => $this->math->normalize($line->acceptedQuantity),
                    'unit_price' => $this->math->normalize($line->unitPrice),
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
        if ($grn->status !== GoodsReceiptNoteStatus::Draft) {
            throw new InvalidArgumentException('Only draft GRNs can be posted.');
        }

        return DB::transaction(function () use ($grn, $postedBy): GoodsReceiptNote {
            $grn->load('lines');
            foreach ($grn->lines as $line) {
                $movement = $this->inventory->receipt($grn, $line, $postedBy);
                if ($movement !== null) {
                    $line->inventory_movement_id = $movement->getKey();
                }
                $line->status = GoodsReceiptNoteLineStatus::Posted;
                $line->save();

                if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
                    $this->orderQuantities->applyReceived($line->purchaseOrderLine, (string) $line->accepted_quantity);
                }
            }

            $grn->status = GoodsReceiptNoteStatus::Posted;
            $grn->posted_by = $postedBy;
            $grn->posted_at = now();
            $grn->save();
            $this->taxDocuments->postGoodsReceiptNote($grn->refresh()->load('lines'));

            return $grn->refresh()->load(['lines', 'adjustments']);
        });
    }

    public function reverse(GoodsReceiptNote $grn, ?int $reversedBy = null): GoodsReceiptNote
    {
        if ($grn->status !== GoodsReceiptNoteStatus::Posted) {
            throw new InvalidArgumentException('Only posted GRNs can be reversed.');
        }

        return DB::transaction(function () use ($grn, $reversedBy): GoodsReceiptNote {
            $grn->load('lines.purchaseOrderLine');
            foreach ($grn->lines as $line) {
                if ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0
                    || $this->math->compare((string) $line->returned_quantity, '0.000000') > 0) {
                    throw new InvalidArgumentException('GRNs with invoiced or returned lines cannot be reversed.');
                }

                $this->inventory->reverseReceipt($grn, $line, $reversedBy);
                if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
                    $this->orderQuantities->reverseReceived($line->purchaseOrderLine, (string) $line->accepted_quantity);
                }
            }

            $grn->status = GoodsReceiptNoteStatus::Reversed;
            $grn->reversed_by = $reversedBy;
            $grn->reversed_at = now();
            $grn->save();

            return $grn->refresh()->load(['purchaseOrder', 'supplier', 'warehouse', 'warehouseLocation', 'lines.item', 'lines.variant', 'lines.uom', 'adjustments']);
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

    /**
     * @return list<object>
     */
    private function receiptLinesAsOrderLines(CreateGoodsReceiptNoteData $data): array
    {
        return array_map(
            static fn ($line): object => (object) [
                'orderedQuantity' => $line->acceptedQuantity,
                'unitPrice' => $line->unitPrice,
                'discountAmount' => $line->discountAmount,
                'taxAmount' => $line->taxAmount,
                'chargeAmount' => $line->chargeAmount,
            ],
            $data->lines,
        );
    }
}
