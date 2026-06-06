<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\Enums\AdjustmentType;

final class StorePurchaseInventoryAdjustmentRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'adjustment_date' => ['required', 'date'],
            'adjustment_type' => ['required', 'in:increase,decrease,recount,damage,expiry,opening_balance'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'reason' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.system_quantity' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.counted_quantity' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.adjustment_quantity' => ['required', 'decimal:0,6'],
            'lines.*.unit_cost' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.reason' => ['nullable', 'string'],
        ];
    }

    public function toData(): StockAdjustmentData
    {
        return new StockAdjustmentData(
            tenantId: $this->tenantId(),
            adjustmentDate: (string) $this->input('adjustment_date'),
            adjustmentType: AdjustmentType::from((string) $this->input('adjustment_type')),
            warehouseId: (int) $this->input('warehouse_id'),
            organizationUnitId: $this->organizationUnitId(),
            warehouseLocationId: $this->filled('warehouse_location_id') ? (int) $this->input('warehouse_location_id') : null,
            reason: (string) $this->input('reason'),
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): StockAdjustmentLineData => new StockAdjustmentLineData(
                itemId: (int) $row['item_id'],
                systemQuantity: (string) $row['system_quantity'],
                countedQuantity: (string) $row['counted_quantity'],
                adjustmentQuantity: (string) $row['adjustment_quantity'],
                unitCost: (string) ($row['unit_cost'] ?? '0.000000'),
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                reason: $row['reason'] ?? null,
            ), $this->input('lines')),
        );
    }
}
