<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Inventory\DTOs\ReservationData;

final class StoreReservationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'reservation_date' => ['required', 'date'],
            'item_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'quantity_reserved' => ['required', 'decimal:0,6', 'gt:0'],
            'reservation_number' => ['nullable', 'string', 'max:100'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'source_type' => ['nullable', 'string', 'max:150'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'source_line_type' => ['nullable', 'string', 'max:150'],
            'source_line_id' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function toData(): ReservationData
    {
        return new ReservationData(
            tenantId: $this->tenantId(),
            reservationDate: (string) $this->input('reservation_date'),
            itemId: (int) $this->input('item_id'),
            warehouseId: (int) $this->input('warehouse_id'),
            quantityReserved: (string) $this->input('quantity_reserved'),
            organizationUnitId: $this->organizationUnitId(),
            reservationNumber: $this->stringOrNull('reservation_number'),
            itemVariantId: $this->intOrNull('item_variant_id'),
            warehouseLocationId: $this->intOrNull('warehouse_location_id'),
            batchId: $this->intOrNull('batch_id'),
            sourceType: $this->stringOrNull('source_type'),
            sourceId: $this->intOrNull('source_id'),
            sourceLineType: $this->stringOrNull('source_line_type'),
            sourceLineId: $this->intOrNull('source_line_id'),
            expiresAt: $this->stringOrNull('expires_at'),
            notes: $this->stringOrNull('notes'),
            uomId: $this->intOrNull('uom_id'),
            createdBy: $this->currentUserId(),
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
