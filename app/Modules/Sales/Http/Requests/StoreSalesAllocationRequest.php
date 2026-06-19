<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Modules\Sales\DTOs\CreateSalesAllocationData;
use Modules\Sales\DTOs\SalesAllocationLineData;

final class StoreSalesAllocationRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'allocation_number' => ['nullable', 'string', 'max:100'],
            'allocation_date' => ['required', 'date'],
            'sales_order_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sales_order_line_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
        ]);
    }

    public function toData(): CreateSalesAllocationData
    {
        return new CreateSalesAllocationData(
            tenantId: $this->tenantId(),
            allocationDate: (string) $this->input('allocation_date'),
            salesOrderId: (int) $this->input('sales_order_id'),
            warehouseId: (int) $this->input('warehouse_id'),
            organizationUnitId: $this->organizationUnitId(),
            allocationNumber: $this->stringOrNull('allocation_number'),
            warehouseLocationId: $this->intOrNull('warehouse_location_id'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): SalesAllocationLineData => new SalesAllocationLineData(
                salesOrderLineId: (int) $row['sales_order_line_id'],
                quantity: (string) $row['quantity'],
            ), $this->input('lines', [])),
        );
    }
}
