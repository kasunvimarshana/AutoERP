<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Purchase\DTOs\CreateGoodsReceiptNoteData;
use Modules\Purchase\DTOs\GoodsReceiptNoteLineData;

final class StoreGoodsReceiptNoteRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'received_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'purchase_order_id' => ['nullable', 'integer', 'min:1'],
            'grn_number' => ['nullable', 'string', 'max:100'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.received_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.accepted_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_price' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.rejected_quantity' => ['nullable', 'decimal:0,6', 'min:0'],
        ];
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
                unitPrice: (string) $row['unit_price'],
                purchaseOrderLineId: isset($row['purchase_order_line_id']) ? (int) $row['purchase_order_line_id'] : null,
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                description: $row['description'] ?? null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                orderedQuantity: (string) ($row['ordered_quantity'] ?? '0.000000'),
                rejectedQuantity: (string) ($row['rejected_quantity'] ?? '0.000000'),
                discountAmount: (string) ($row['discount_amount'] ?? '0.000000'),
                taxAmount: (string) ($row['tax_amount'] ?? '0.000000'),
                chargeAmount: (string) ($row['charge_amount'] ?? '0.000000'),
            ), $this->input('lines')),
        );
    }

    private function intOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? (string) $this->input($key) : null;
    }
}
