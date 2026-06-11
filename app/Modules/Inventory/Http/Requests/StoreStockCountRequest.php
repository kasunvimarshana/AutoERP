<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Inventory\DTOs\StockCountData;
use Modules\Inventory\DTOs\StockCountLineData;

final class StoreStockCountRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'count_date' => ['required', 'date'],
            'count_number' => ['nullable', 'string', 'max:100'],
            'count_type' => ['nullable', Rule::in(['stock_count', 'cycle_count'])],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.batch_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.serial_number_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.system_quantity' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.counted_quantity' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): StockCountData
    {
        return new StockCountData(
            tenantId: $this->tenantId(),
            countDate: (string) $this->input('count_date'),
            warehouseId: (int) $this->input('warehouse_id'),
            organizationUnitId: $this->organizationUnitId(),
            countNumber: $this->stringOrNull('count_number'),
            countType: (string) ($this->input('count_type') ?? 'stock_count'),
            warehouseLocationId: $this->intOrNull('warehouse_location_id'),
            reason: $this->stringOrNull('reason'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            lines: array_map(fn (array $row): StockCountLineData => new StockCountLineData(
                itemId: (int) $row['item_id'],
                countedQuantity: (string) $row['counted_quantity'],
                systemQuantity: isset($row['system_quantity']) ? (string) $row['system_quantity'] : null,
                unitCost: isset($row['unit_cost']) ? (string) $row['unit_cost'] : null,
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                batchId: isset($row['batch_id']) ? (int) $row['batch_id'] : null,
                serialNumberId: isset($row['serial_number_id']) ? (int) $row['serial_number_id'] : null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                notes: isset($row['notes']) ? (string) $row['notes'] : null,
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
