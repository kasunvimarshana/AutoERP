<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\Enums\GoodsReceiptNoteLineStatus;
use Modules\Purchase\Enums\GoodsReceiptNoteStatus;
use Modules\Purchase\Models\GoodsReceiptNote;
use Modules\Purchase\Models\PurchaseHeaderAdjustment;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Validators\PurchaseValidationService;

final class GoodsReceiptNoteService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseValidationService $validator,
        private readonly PurchaseOrderCalculationService $calculator,
        private readonly PurchaseHeaderAdjustmentService $adjustments,
        private readonly PurchaseOrderService $orders,
        private readonly PurchaseInventoryIntegrationService $inventory,
        private readonly PurchaseNumberService $numbers,
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
            $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId);
            if ($line->purchaseOrderLineId !== null) {
                $poLine = PurchaseOrderLine::query()->findOrFail($line->purchaseOrderLineId);
                $this->validator->assertReceiptWithinOrder($poLine, $line->acceptedQuantity);
            }
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
                $grn->lines()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'purchase_order_line_id' => $line->purchaseOrderLineId,
                    'item_id' => $line->itemId,
                    'item_variant_id' => $line->itemVariantId,
                    'description' => $line->description,
                    'uom_id' => $line->uomId,
                    'ordered_quantity' => $this->math->normalize($line->orderedQuantity),
                    'received_quantity' => $this->math->normalize($line->receivedQuantity),
                    'accepted_quantity' => $this->math->normalize($line->acceptedQuantity),
                    'rejected_quantity' => $this->math->normalize($line->rejectedQuantity),
                    'remaining_quantity' => $this->math->normalize($line->acceptedQuantity),
                    'unit_price' => $this->math->normalize($line->unitPrice),
                    'discount_amount' => $this->math->normalize($line->discountAmount),
                    'tax_amount' => $this->math->normalize($line->taxAmount),
                    'charge_amount' => $this->math->normalize($line->chargeAmount),
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
            $grn->load('lines');
            foreach ($grn->lines as $line) {
                $movement = $this->inventory->receipt($grn, $line, $postedBy);
                if ($movement !== null) {
                    $line->inventory_movement_id = $movement->getKey();
                }
                $line->status = GoodsReceiptNoteLineStatus::Posted;
                $line->save();

                if ($line->purchaseOrderLine instanceof PurchaseOrderLine) {
                    $this->orders->applyReceived($line->purchaseOrderLine, (string) $line->accepted_quantity);
                }
            }

            $grn->status = GoodsReceiptNoteStatus::Posted;
            $grn->posted_by = $postedBy;
            $grn->posted_at = now();
            $grn->save();

            return $grn->refresh()->load(['lines', 'adjustments']);
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
