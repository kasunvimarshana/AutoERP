<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\Enums\PurchaseOrderLineStatus;
use Modules\Purchase\Enums\PurchaseOrderStatus;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderLine;
use Modules\Purchase\Validators\PurchaseValidationService;

final class PurchaseOrderService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly PurchaseValidationService $validator,
        private readonly PurchaseOrderCalculationService $calculator,
        private readonly PurchaseHeaderAdjustmentService $adjustments,
        private readonly PurchaseNumberService $numbers,
        private readonly PurchaseStatusService $statuses,
    ) {}

    public function create(CreatePurchaseOrderData $data): PurchaseOrder
    {
        if ($data->warehouseId !== null) {
            $this->validator->warehouse($data->tenantId, $data->organizationUnitId, $data->warehouseId);
        }

        foreach ($data->lines as $line) {
            $this->validator->assertPositiveQuantity($line->orderedQuantity);
            $this->validator->assertNonNegative($line->unitPrice, 'Purchase unit price cannot be negative.');
            $this->validator->item($data->tenantId, $data->organizationUnitId, $line->itemId);
        }

        return DB::transaction(function () use ($data): PurchaseOrder {
            $calculation = $this->calculator->calculate($data->lines, $data->adjustments);
            $order = PurchaseOrder::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'supplier_type' => $data->supplierType,
                'supplier_id' => $data->supplierId,
                'warehouse_id' => $data->warehouseId,
                'warehouse_location_id' => $data->warehouseLocationId,
                'purchase_order_number' => $data->purchaseOrderNumber ?? $this->numbers->next($data->tenantId, 'PO', 'purchase_orders', 'purchase_order_number'),
                'purchase_order_date' => $data->purchaseOrderDate,
                'expected_delivery_date' => $data->expectedDeliveryDate,
                'currency_id' => $data->currencyId,
                'exchange_rate' => $this->math->normalize($data->exchangeRate),
                'status' => PurchaseOrderStatus::Draft,
                'subtotal' => $calculation->subtotal,
                'discount_total' => $calculation->discountTotal,
                'tax_total' => $calculation->taxTotal,
                'charge_total' => $calculation->chargeTotal,
                'adjustment_total' => $calculation->adjustmentTotal,
                'grand_total' => $calculation->grandTotal,
                'notes' => $data->notes,
                'created_by' => $data->createdBy,
            ]);

            foreach ($data->lines as $index => $line) {
                $order->lines()->create([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'line_number' => $index + 1,
                    'item_id' => $line->itemId,
                    'item_variant_id' => $line->itemVariantId,
                    'description' => $line->description,
                    'uom_id' => $line->uomId,
                    'ordered_quantity' => $this->math->normalize($line->orderedQuantity),
                    'remaining_quantity' => $this->math->normalize($line->orderedQuantity),
                    'unit_price' => $this->math->normalize($line->unitPrice),
                    'discount_amount' => $this->math->normalize($line->discountAmount),
                    'tax_amount' => $this->math->normalize($line->taxAmount),
                    'charge_amount' => $this->math->normalize($line->chargeAmount),
                    'line_total' => $calculation->lineTotals[$index],
                    'status' => PurchaseOrderLineStatus::Open,
                ]);
            }

            foreach ($data->adjustments as $adjustment) {
                $this->adjustments->create($data->tenantId, $data->organizationUnitId, 'purchase_order', (int) $order->getKey(), $adjustment);
            }

            return $order->load(['lines', 'adjustments']);
        });
    }

    public function approve(PurchaseOrder $order, ?int $approvedBy = null): PurchaseOrder
    {
        $this->statuses->assertPurchaseOrderTransition($order->status, PurchaseOrderStatus::Approved);
        $order->status = PurchaseOrderStatus::Approved;
        $order->approved_by = $approvedBy;
        $order->approved_at = now();
        $order->save();

        return $order->refresh();
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        $this->statuses->assertPurchaseOrderTransition($order->status, PurchaseOrderStatus::Cancelled);
        $order->status = PurchaseOrderStatus::Cancelled;
        $order->save();

        return $order->refresh();
    }

    public function close(PurchaseOrder $order, ?int $closedBy = null): PurchaseOrder
    {
        $this->statuses->assertPurchaseOrderTransition($order->status, PurchaseOrderStatus::Closed);
        $order->status = PurchaseOrderStatus::Closed;
        $order->closed_by = $closedBy;
        $order->closed_at = now();
        $order->save();

        return $order->refresh();
    }

    public function applyReceived(PurchaseOrderLine $line, string $quantity): void
    {
        $line->received_quantity = $this->math->add((string) $line->received_quantity, $quantity);
        $line->remaining_quantity = $this->math->sub((string) $line->ordered_quantity, (string) $line->received_quantity);
        $line->status = $this->math->isZero((string) $line->remaining_quantity)
            ? PurchaseOrderLineStatus::Received
            : PurchaseOrderLineStatus::PartiallyReceived;
        $line->save();
        $this->refreshOrderStatus($line->order);
    }

    public function applyInvoiced(PurchaseOrderLine $line, string $quantity): void
    {
        $line->invoiced_quantity = $this->math->add((string) $line->invoiced_quantity, $quantity);
        if ($this->math->compare((string) $line->invoiced_quantity, (string) $line->ordered_quantity) >= 0) {
            $line->status = PurchaseOrderLineStatus::Invoiced;
        } elseif ($this->math->compare((string) $line->invoiced_quantity, '0.000000') > 0) {
            $line->status = PurchaseOrderLineStatus::PartiallyInvoiced;
        }
        $line->save();
        $this->refreshOrderStatus($line->order);
    }

    private function refreshOrderStatus(?PurchaseOrder $order): void
    {
        if (! $order instanceof PurchaseOrder) {
            return;
        }

        $order->load('lines');
        $ordered = $this->math->sum($order->lines->pluck('ordered_quantity')->all());
        $received = $this->math->sum($order->lines->pluck('received_quantity')->all());
        $invoiced = $this->math->sum($order->lines->pluck('invoiced_quantity')->all());

        if ($this->math->compare($invoiced, $ordered) >= 0) {
            $order->status = PurchaseOrderStatus::Invoiced;
        } elseif ($this->math->compare($invoiced, '0.000000') > 0) {
            $order->status = PurchaseOrderStatus::PartiallyInvoiced;
        } elseif ($this->math->compare($received, $ordered) >= 0) {
            $order->status = PurchaseOrderStatus::Received;
        } elseif ($this->math->compare($received, '0.000000') > 0) {
            $order->status = PurchaseOrderStatus::PartiallyReceived;
        }

        $order->save();
    }
}
