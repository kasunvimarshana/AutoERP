<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Inventory\DTOs\StockTransferData;
use Modules\Inventory\DTOs\StockTransferLineData;

final class StoreTransferRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'transfer_date' => ['required', 'date'],
            'from_warehouse_id' => ['required', 'integer', 'min:1', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'integer', 'min:1'],
            'from_warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'to_warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'transfer_number' => ['nullable', 'string', 'max:100'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.batch_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.serial_number_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'decimal:0,6', 'min:0'],
        ];
    }

    public function toData(): StockTransferData
    {
        return new StockTransferData(
            tenantId: $this->tenantId(),
            transferDate: (string) $this->input('transfer_date'),
            fromWarehouseId: (int) $this->input('from_warehouse_id'),
            toWarehouseId: (int) $this->input('to_warehouse_id'),
            organizationUnitId: $this->organizationUnitId(),
            transferNumber: $this->filled('transfer_number') ? (string) $this->input('transfer_number') : null,
            fromWarehouseLocationId: $this->filled('from_warehouse_location_id') ? (int) $this->input('from_warehouse_location_id') : null,
            toWarehouseLocationId: $this->filled('to_warehouse_location_id') ? (int) $this->input('to_warehouse_location_id') : null,
            reason: $this->filled('reason') ? (string) $this->input('reason') : null,
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): StockTransferLineData => new StockTransferLineData(
                itemId: (int) $row['item_id'],
                quantity: (string) $row['quantity'],
                unitCost: (string) ($row['unit_cost'] ?? '0.000000'),
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                batchId: isset($row['batch_id']) ? (int) $row['batch_id'] : null,
                serialNumberId: isset($row['serial_number_id']) ? (int) $row['serial_number_id'] : null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
            ), $this->input('lines')),
        );
    }
}
