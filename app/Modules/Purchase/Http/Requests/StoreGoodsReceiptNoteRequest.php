<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\GoodsReceiptBatchAllocationData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;

final class StoreGoodsReceiptNoteRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'received_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'purchase_order_id' => ['nullable', 'integer', 'min:1'],
            'grn_number' => ['nullable', 'string', 'max:100'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['required_without:purchase_order_id', 'nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.received_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.accepted_quantity' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.unit_price' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.purchase_order_line_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.ordered_uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.base_uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.ordered_quantity' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.rejected_quantity' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_group_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.charge_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.batch_allocations' => ['nullable', 'array'],
            'lines.*.batch_allocations.*.batch_id' => ['nullable', 'integer', 'min:1', 'required_without:lines.*.batch_allocations.*.batch_number'],
            'lines.*.batch_allocations.*.batch_number' => ['nullable', 'string', 'max:120', 'required_without:lines.*.batch_allocations.*.batch_id'],
            'lines.*.batch_allocations.*.lot_number' => ['nullable', 'string', 'max:120'],
            'lines.*.batch_allocations.*.manufacture_date' => ['nullable', 'date'],
            'lines.*.batch_allocations.*.expiry_date' => ['nullable', 'date'],
            'lines.*.batch_allocations.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
        ]);
    }

    public function toData(): CreateGoodsReceiptNoteData
    {
        return new CreateGoodsReceiptNoteData(
            tenantId: $this->tenantId(),
            receivedDate: (string) $this->input('received_date'),
            warehouseId: (int) $this->input('warehouse_id'),
            organizationUnitId: $this->organizationUnitId(),
            purchaseOrderId: $this->intOrNull('purchase_order_id'),
            grnNumber: $this->stringOrNull('grn_number'),
            warehouseLocationId: $this->intOrNull('warehouse_location_id'),
            supplierType: $this->stringOrNull('supplier_type'),
            supplierId: $this->intOrNull('supplier_id'),
            notes: $this->stringOrNull('notes'),
            receivedBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): GoodsReceiptNoteLineData => new GoodsReceiptNoteLineData(
                itemId: (int) $row['item_id'],
                receivedQuantity: (string) $row['received_quantity'],
                acceptedQuantity: (string) $row['accepted_quantity'],
                unitPrice: (string) ($row['unit_price'] ?? '0.000000'),
                purchaseOrderLineId: isset($row['purchase_order_line_id']) ? (int) $row['purchase_order_line_id'] : null,
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                description: $row['description'] ?? null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                orderedUomId: isset($row['ordered_uom_id']) ? (int) $row['ordered_uom_id'] : null,
                baseUomId: isset($row['base_uom_id']) ? (int) $row['base_uom_id'] : null,
                orderedQuantity: (string) ($row['ordered_quantity'] ?? '0.000000'),
                rejectedQuantity: (string) ($row['rejected_quantity'] ?? '0.000000'),
                discountAmount: (string) ($row['discount_amount'] ?? '0.000000'),
                taxAmount: (string) ($row['tax_amount'] ?? '0.000000'),
                chargeAmount: (string) ($row['charge_amount'] ?? '0.000000'),
                taxGroupId: isset($row['tax_group_id']) ? (int) $row['tax_group_id'] : null,
                batchAllocations: array_map(static fn (array $allocation): GoodsReceiptBatchAllocationData => new GoodsReceiptBatchAllocationData(
                    quantity: (string) $allocation['quantity'],
                    batchId: isset($allocation['batch_id']) ? (int) $allocation['batch_id'] : null,
                    batchNumber: isset($allocation['batch_number']) ? (string) $allocation['batch_number'] : null,
                    lotNumber: isset($allocation['lot_number']) ? (string) $allocation['lot_number'] : null,
                    manufactureDate: isset($allocation['manufacture_date']) ? (string) $allocation['manufacture_date'] : null,
                    expiryDate: isset($allocation['expiry_date']) ? (string) $allocation['expiry_date'] : null,
                ), $row['batch_allocations'] ?? []),
            ), $this->input('lines')),
        );
    }
}
