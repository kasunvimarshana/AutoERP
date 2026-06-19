<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Purchase\DTOs\CreatePurchaseOrderData;
use Modules\Purchase\DTOs\PurchaseHeaderAdjustmentData;
use Modules\Purchase\DTOs\PurchaseOrderLineData;
use Modules\Purchase\Enums\PurchaseAdjustmentAllocationMethod;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationBase;
use Modules\Purchase\Enums\PurchaseAdjustmentCalculationType;
use Modules\Purchase\Enums\PurchaseAdjustmentEffect;
use Modules\Purchase\Enums\PurchaseAdjustmentType;

class StorePurchaseOrderRequest extends PurchaseRequest
{
    public function rules(): array
    {
        return array_merge($this->scopeRules(), [
            'purchase_order_date' => ['required', 'date'],
            'purchase_order_number' => ['nullable', 'string', 'max:100'],
            'supplier_type' => ['nullable', 'string', 'max:150'],
            'supplier_id' => ['required', 'integer', 'min:1'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:purchase_order_date'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.client_line_key' => ['nullable', 'string', 'max:100', 'distinct'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.uom_id' => ['required', 'integer', 'min:1'],
            'lines.*.ordered_uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.base_uom_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.ordered_quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_price' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.discount_calculation_type' => ['nullable', Rule::enum(PurchaseAdjustmentCalculationType::class)],
            'lines.*.discount_rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_calculation_type' => ['nullable', Rule::enum(PurchaseAdjustmentCalculationType::class)],
            'lines.*.tax_rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'lines.*.tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_group_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.charge_calculation_type' => ['nullable', Rule::enum(PurchaseAdjustmentCalculationType::class)],
            'lines.*.charge_rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'lines.*.charge_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'adjustments' => ['nullable', 'array'],
            'adjustments.*.name' => ['required', 'string', 'max:255'],
            'adjustments.*.adjustment_type' => ['required', Rule::enum(PurchaseAdjustmentType::class)],
            'adjustments.*.effect' => ['required', Rule::enum(PurchaseAdjustmentEffect::class)],
            'adjustments.*.calculation_type' => ['nullable', Rule::enum(PurchaseAdjustmentCalculationType::class)],
            'adjustments.*.calculation_base' => ['nullable', Rule::enum(PurchaseAdjustmentCalculationBase::class)],
            'adjustments.*.rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'adjustments.*.amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'adjustments.*.allocation_method' => ['nullable', Rule::enum(PurchaseAdjustmentAllocationMethod::class)],
            'adjustments.*.is_allocatable' => ['nullable', 'boolean'],
            'adjustments.*.finance_posting_profile_id' => ['nullable', 'integer', 'min:1'],
            'adjustments.*.finance_account_id' => ['nullable', 'integer', 'min:1'],
            'adjustments.*.cost_treatment' => ['nullable', 'string', 'max:80'],
            'adjustments.*.tax_treatment' => ['nullable', 'string', 'max:80'],
            'adjustments.*.mapping_source' => ['nullable', 'in:catalogue,override'],
            'adjustments.*.override_reason' => ['nullable', 'string', 'max:1000'],
            'adjustments.*.sort_order' => ['nullable', 'integer'],
            'adjustments.*.description' => ['nullable', 'string'],
            'adjustments.*.allocations' => ['nullable', 'array'],
            'adjustments.*.allocations.*.client_line_key' => ['nullable', 'string', 'max:100'],
            'adjustments.*.allocations.*.purchase_order_line_id' => ['nullable', 'integer', 'min:1'],
            'adjustments.*.allocations.*.amount' => ['required_with:adjustments.*.allocations', 'decimal:0,6', 'min:0'],
        ]);
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
                orderedUomId: isset($row['ordered_uom_id']) ? (int) $row['ordered_uom_id'] : null,
                baseUomId: isset($row['base_uom_id']) ? (int) $row['base_uom_id'] : null,
                discountCalculationType: PurchaseAdjustmentCalculationType::from((string) ($row['discount_calculation_type'] ?? 'fixed')),
                discountRate: (string) ($row['discount_rate'] ?? '0.000000'),
                discountAmount: (string) ($row['discount_amount'] ?? '0.000000'),
                taxCalculationType: PurchaseAdjustmentCalculationType::from((string) ($row['tax_calculation_type'] ?? 'fixed')),
                taxRate: (string) ($row['tax_rate'] ?? '0.000000'),
                taxAmount: (string) ($row['tax_amount'] ?? '0.000000'),
                chargeCalculationType: PurchaseAdjustmentCalculationType::from((string) ($row['charge_calculation_type'] ?? 'fixed')),
                chargeRate: (string) ($row['charge_rate'] ?? '0.000000'),
                chargeAmount: (string) ($row['charge_amount'] ?? '0.000000'),
                taxGroupId: isset($row['tax_group_id']) ? (int) $row['tax_group_id'] : null,
                clientLineKey: isset($row['client_line_key']) ? (string) $row['client_line_key'] : null,
            ), $this->input('lines')),
            adjustments: array_map(static fn (array $row): PurchaseHeaderAdjustmentData => new PurchaseHeaderAdjustmentData(
                name: (string) $row['name'],
                adjustmentType: PurchaseAdjustmentType::from((string) $row['adjustment_type']),
                effect: PurchaseAdjustmentEffect::from((string) $row['effect']),
                amount: (string) ($row['amount'] ?? '0.000000'),
                calculationType: PurchaseAdjustmentCalculationType::from((string) ($row['calculation_type'] ?? 'fixed')),
                calculationBase: PurchaseAdjustmentCalculationBase::from((string) ($row['calculation_base'] ?? 'subtotal')),
                rate: (string) ($row['rate'] ?? '0.000000'),
                allocationMethod: PurchaseAdjustmentAllocationMethod::from((string) ($row['allocation_method'] ?? 'proportional')),
                isAllocatable: (bool) ($row['is_allocatable'] ?? true),
                sortOrder: (int) ($row['sort_order'] ?? 0),
                description: $row['description'] ?? null,
                financePostingProfileId: isset($row['finance_posting_profile_id']) ? (int) $row['finance_posting_profile_id'] : null,
                financeAccountId: isset($row['finance_account_id']) ? (int) $row['finance_account_id'] : null,
                costTreatment: $row['cost_treatment'] ?? null,
                taxTreatment: $row['tax_treatment'] ?? null,
                mappingSource: $row['mapping_source'] ?? null,
                overrideReason: $row['override_reason'] ?? null,
                manualAllocations: array_map(static fn (array $allocation): array => [
                    'client_line_key' => $allocation['client_line_key'] ?? null,
                    'purchase_order_line_id' => isset($allocation['purchase_order_line_id']) ? (int) $allocation['purchase_order_line_id'] : null,
                    'amount' => (string) $allocation['amount'],
                ], $row['allocations'] ?? []),
            ), $this->input('adjustments', [])),
        );
    }
}
