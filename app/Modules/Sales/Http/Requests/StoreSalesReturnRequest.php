<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Sales\DTOs\CreateSalesReturnData;
use Modules\Sales\DTOs\SalesReturnLineData;
use Modules\Sales\Enums\SalesReturnType;

final class StoreSalesReturnRequest extends SalesRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'return_number' => ['nullable', 'string', 'max:100'],
            'return_date' => ['required', 'date'],
            'customer_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'return_type' => ['required', Rule::enum(SalesReturnType::class)],
            'reason' => ['nullable', 'string'],
            'replacement_sales_order_id' => ['nullable', 'integer', 'min:1'],
            'approval_required' => ['nullable', 'boolean'],
            'cost_basis' => ['nullable', 'decimal:0,6', 'min:0'],
            'audit_metadata' => ['nullable', 'array'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.source_line_type' => ['nullable', 'in:sales_delivery_line,invoice_line,sales_order_line'],
            'lines.*.source_line_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.item_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.returned_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_price' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.cost_basis' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.condition_status' => ['nullable', 'in:sellable,damaged,quarantine,scrap'],
            'lines.*.reason' => ['nullable', 'string'],
        ]);
    }

    public function toData(): CreateSalesReturnData
    {
        return new CreateSalesReturnData(
            tenantId: $this->tenantId(),
            returnDate: (string) $this->input('return_date'),
            customerId: (int) $this->input('customer_id'),
            returnType: SalesReturnType::from((string) $this->input('return_type')),
            organizationUnitId: $this->organizationUnitId(),
            returnNumber: $this->stringOrNull('return_number'),
            warehouseId: $this->intOrNull('warehouse_id'),
            warehouseLocationId: $this->intOrNull('warehouse_location_id'),
            reason: $this->stringOrNull('reason'),
            replacementSalesOrderId: $this->intOrNull('replacement_sales_order_id'),
            approvalRequired: (bool) $this->input('approval_required', false),
            costBasis: $this->stringOrNull('cost_basis'),
            auditMetadata: $this->input('audit_metadata'),
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): SalesReturnLineData => new SalesReturnLineData(
                returnedQuantity: (string) $row['returned_quantity'],
                sourceLineType: $row['source_line_type'] ?? null,
                sourceLineId: isset($row['source_line_id']) ? (int) $row['source_line_id'] : null,
                itemId: isset($row['item_id']) ? (int) $row['item_id'] : null,
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                unitPrice: isset($row['unit_price']) ? (string) $row['unit_price'] : null,
                costBasis: isset($row['cost_basis']) ? (string) $row['cost_basis'] : null,
                conditionStatus: (string) ($row['condition_status'] ?? 'sellable'),
                reason: $row['reason'] ?? null,
            ), $this->input('lines', [])),
        );
    }
}
