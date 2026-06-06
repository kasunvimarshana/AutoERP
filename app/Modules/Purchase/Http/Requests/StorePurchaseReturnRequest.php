<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\DTOs\PurchaseReturnLineData;

final class StorePurchaseReturnRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'return_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'return_number' => ['nullable', 'string', 'max:100'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.source_line_type' => ['required', 'in:goods_receipt_note_line'],
            'lines.*.source_line_id' => ['required', 'integer', 'min:1'],
            'lines.*.returned_quantity' => ['required', 'decimal:0,6', 'gt:0'],
        ];
    }

    public function toData(): CreatePurchaseReturnData
    {
        return new CreatePurchaseReturnData(
            tenantId: $this->tenantId(),
            returnDate: (string) $this->input('return_date'),
            warehouseId: (int) $this->input('warehouse_id'),
            organizationUnitId: $this->organizationUnitId(),
            returnNumber: $this->filled('return_number') ? (string) $this->input('return_number') : null,
            warehouseLocationId: $this->filled('warehouse_location_id') ? (int) $this->input('warehouse_location_id') : null,
            supplierType: $this->filled('supplier_type') ? (string) $this->input('supplier_type') : null,
            supplierId: $this->filled('supplier_id') ? (int) $this->input('supplier_id') : null,
            reason: $this->filled('reason') ? (string) $this->input('reason') : null,
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): PurchaseReturnLineData => new PurchaseReturnLineData(
                sourceLineType: (string) $row['source_line_type'],
                sourceLineId: (int) $row['source_line_id'],
                returnedQuantity: (string) $row['returned_quantity'],
                reason: $row['reason'] ?? null,
            ), $this->input('lines')),
        );
    }
}
