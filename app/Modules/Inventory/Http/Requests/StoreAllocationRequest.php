<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Inventory\DTOs\AllocationData;

final class StoreAllocationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'allocation_date' => ['required', 'date'],
            'item_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'quantity_allocated' => ['required', 'decimal:0,6', 'gt:0'],
            'allocation_number' => ['nullable', 'string', 'max:100'],
            'reservation_id' => ['nullable', 'integer', 'min:1'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'serial_number_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'source_type' => ['nullable', 'string', 'max:150'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_line_type' => ['nullable', 'string', 'max:150'],
            'source_line_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): AllocationData
    {
        $int = fn (string $key): ?int => $this->filled($key) ? (int) $this->input($key) : null;
        $string = fn (string $key): ?string => $this->filled($key) ? (string) $this->input($key) : null;

        return new AllocationData(
            tenantId: $this->tenantId(),
            allocationDate: (string) $this->input('allocation_date'),
            itemId: (int) $this->input('item_id'),
            warehouseId: (int) $this->input('warehouse_id'),
            quantityAllocated: (string) $this->input('quantity_allocated'),
            organizationUnitId: $this->organizationUnitId(),
            allocationNumber: $string('allocation_number'),
            reservationId: $int('reservation_id'),
            itemVariantId: $int('item_variant_id'),
            warehouseLocationId: $int('warehouse_location_id'),
            batchId: $int('batch_id'),
            serialNumberId: $int('serial_number_id'),
            sourceType: $string('source_type'),
            sourceId: $int('source_id'),
            sourceLineType: $string('source_line_type'),
            sourceLineId: $int('source_line_id'),
            notes: $string('notes'),
            uomId: $int('uom_id'),
            createdBy: $this->currentUserId(),
        );
    }
}
