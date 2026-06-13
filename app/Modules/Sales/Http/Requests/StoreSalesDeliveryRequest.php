<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Modules\Sales\DTOs\CreateSalesDeliveryData;
use Modules\Sales\DTOs\SalesDeliveryLineData;

final class StoreSalesDeliveryRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'delivery_number' => ['nullable', 'string', 'max:100'],
            'delivery_date' => ['required', 'date'],
            'sales_order_id' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sales_order_line_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.ordered_quantity' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.delivered_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_price' => ['required', 'decimal:0,6', 'min:0'],
        ]);
    }

    public function toData(): CreateSalesDeliveryData
    {
        return new CreateSalesDeliveryData(
            tenantId: $this->tenantId(),
            deliveryDate: (string) $this->input('delivery_date'),
            customerId: (int) $this->input('customer_id'),
            warehouseId: (int) $this->input('warehouse_id'),
            organizationUnitId: $this->organizationUnitId(),
            deliveryNumber: $this->stringOrNull('delivery_number'),
            salesOrderId: $this->intOrNull('sales_order_id'),
            warehouseLocationId: $this->intOrNull('warehouse_location_id'),
            notes: $this->stringOrNull('notes'),
            deliveredBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): SalesDeliveryLineData => new SalesDeliveryLineData(
                itemId: (int) $row['item_id'],
                deliveredQuantity: (string) $row['delivered_quantity'],
                unitPrice: (string) $row['unit_price'],
                salesOrderLineId: isset($row['sales_order_line_id']) ? (int) $row['sales_order_line_id'] : null,
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                description: $row['description'] ?? null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                orderedQuantity: (string) ($row['ordered_quantity'] ?? '0.000000'),
            ), $this->input('lines', [])),
        );
    }
}
