<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\DTOs\PurchaseOrderLineData;
use Modules\Purchase\Enums\PurchaseAdjustmentAllocationMethod;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;

final class StorePurchaseOrderRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'purchase_order_date' => ['required', 'date'],
            'purchase_order_number' => ['nullable', 'string', 'max:100'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:purchase_order_date'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.ordered_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_price' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.charge_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'adjustments' => ['nullable', 'array'],
            'adjustments.*.name' => ['required', 'string', 'max:255'],
            'adjustments.*.adjustment_type' => ['required', Rule::enum(PurchaseAdjustmentType::class)],
            'adjustments.*.effect' => ['required', Rule::enum(PurchaseAdjustmentEffect::class)],
            'adjustments.*.amount' => ['required', 'decimal:0,6', 'min:0'],
        ];
    }

    public function toData(): CreatePurchaseOrderData
    {
        return new CreatePurchaseOrderData(
            tenantId: $this->tenantId(),
            purchaseOrderDate: (string) $this->input('purchase_order_date'),
            organizationUnitId: $this->organizationUnitId(),
            purchaseOrderNumber: $this->stringOrNull('purchase_order_number'),
            supplierType: $this->stringOrNull('supplier_type'),
            supplierId: $this->intOrNull('supplier_id'),
            warehouseId: $this->intOrNull('warehouse_id'),
            warehouseLocationId: $this->intOrNull('warehouse_location_id'),
            expectedDeliveryDate: $this->stringOrNull('expected_delivery_date'),
            currencyId: $this->intOrNull('currency_id'),
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            lines: array_map(static fn (array $row): PurchaseOrderLineData => new PurchaseOrderLineData(
                itemId: (int) $row['item_id'],
                orderedQuantity: (string) $row['ordered_quantity'],
                unitPrice: (string) $row['unit_price'],
                itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
                description: $row['description'] ?? null,
                uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
                discountAmount: (string) ($row['discount_amount'] ?? '0.000000'),
                taxAmount: (string) ($row['tax_amount'] ?? '0.000000'),
                chargeAmount: (string) ($row['charge_amount'] ?? '0.000000'),
            ), $this->input('lines')),
            adjustments: array_map(static fn (array $row): PurchaseHeaderAdjustmentData => new PurchaseHeaderAdjustmentData(
                name: (string) $row['name'],
                adjustmentType: PurchaseAdjustmentType::from((string) $row['adjustment_type']),
                effect: PurchaseAdjustmentEffect::from((string) $row['effect']),
                amount: (string) $row['amount'],
                calculationType: PurchaseAdjustmentCalculationType::from((string) ($row['calculation_type'] ?? 'fixed')),
                rate: (string) ($row['rate'] ?? '0.000000'),
                allocationMethod: PurchaseAdjustmentAllocationMethod::from((string) ($row['allocation_method'] ?? 'proportional')),
                isAllocatable: (bool) ($row['is_allocatable'] ?? true),
                sortOrder: (int) ($row['sort_order'] ?? 0),
                description: $row['description'] ?? null,
            ), $this->input('adjustments', [])),
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
