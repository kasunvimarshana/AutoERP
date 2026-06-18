<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Modules\Purchase\DTOs\CreatePurchaseReturnData;
use Modules\Purchase\DTOs\PurchaseReturnLineData;
use Modules\Purchase\Enums\PurchaseReturnType;

final class StorePurchaseReturnRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'return_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'return_number' => ['nullable', 'string', 'max:100'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string'],
            'return_type' => ['nullable', 'in:referenced,manual_supplier_return'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'approval_required' => ['nullable', 'boolean'],
            'affects_supplier_balance' => ['nullable', 'boolean'],
            'cost_basis' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.source_line_type' => ['required', 'in:goods_receipt_note_line,manual_supplier_return'],
            'lines.*.source_line_id' => ['required', 'integer', 'min:0'],
            'lines.*.returned_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.item_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.cost_basis' => ['nullable', 'decimal:0,6', 'min:0'],
        ]);
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
            returnType: PurchaseReturnType::from((string) $this->input('return_type', 'referenced')),
            sourceType: $this->filled('source_type') ? (string) $this->input('source_type') : null,
            sourceId: $this->filled('source_id') ? (int) $this->input('source_id') : null,
            approvalRequired: (bool) $this->input('approval_required', false),
            affectsSupplierBalance: (bool) $this->input('affects_supplier_balance', true),
            costBasis: $this->filled('cost_basis') ? (string) $this->input('cost_basis') : null,
            auditMetadata: $this->input('audit_metadata'),
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): PurchaseReturnLineData => new PurchaseReturnLineData(
                sourceLineType: (string) $row['source_line_type'],
                sourceLineId: (int) $row['source_line_id'],
                returnedQuantity: (string) $row['returned_quantity'],
                itemId: isset($row['item_id']) ? (int) $row['item_id'] : null,
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                unitPrice: isset($row['unit_price']) ? (string) $row['unit_price'] : null,
                costBasis: isset($row['cost_basis']) ? (string) $row['cost_basis'] : null,
                reason: $row['reason'] ?? null,
            ), $this->input('lines')),
        );
    }
}
