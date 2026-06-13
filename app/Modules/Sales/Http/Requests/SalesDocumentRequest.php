<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Sales\DTOs\SalesHeaderAdjustmentData;
use Modules\Sales\DTOs\SalesLineData;
use Modules\Sales\Enums\SalesAdjustmentAllocationMethod;
use Modules\Sales\Enums\SalesAdjustmentCalculationBase;
use Modules\Sales\Enums\SalesAdjustmentCalculationType;
use Modules\Sales\Enums\SalesAdjustmentEffect;
use Modules\Sales\Enums\SalesAdjustmentType;

abstract class SalesDocumentRequest extends SalesRequest
{
    protected function documentRules(string $dateField): array
    {
        return array_merge($this->scopeRules(), [
            $dateField => ['required', 'date'],
            'customer_id' => ['required', 'integer', 'min:1'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'min:1'],
            'lines.*.item_variant_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.uom_id' => ['required', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_price' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.discount_calculation_type' => ['nullable', Rule::enum(SalesAdjustmentCalculationType::class)],
            'lines.*.discount_rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.tax_calculation_type' => ['nullable', Rule::enum(SalesAdjustmentCalculationType::class)],
            'lines.*.tax_rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'lines.*.tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.charge_calculation_type' => ['nullable', Rule::enum(SalesAdjustmentCalculationType::class)],
            'lines.*.charge_rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'lines.*.charge_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'adjustments' => ['nullable', 'array'],
            'adjustments.*.name' => ['required', 'string', 'max:255'],
            'adjustments.*.adjustment_type' => ['required', Rule::enum(SalesAdjustmentType::class)],
            'adjustments.*.effect' => ['required', Rule::enum(SalesAdjustmentEffect::class)],
            'adjustments.*.calculation_type' => ['nullable', Rule::enum(SalesAdjustmentCalculationType::class)],
            'adjustments.*.calculation_base' => ['nullable', Rule::enum(SalesAdjustmentCalculationBase::class)],
            'adjustments.*.rate' => ['nullable', 'decimal:0,6', 'min:0', 'max:100'],
            'adjustments.*.amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'adjustments.*.allocation_method' => ['nullable', Rule::enum(SalesAdjustmentAllocationMethod::class)],
            'adjustments.*.is_allocatable' => ['nullable', 'boolean'],
            'adjustments.*.sort_order' => ['nullable', 'integer'],
            'adjustments.*.description' => ['nullable', 'string'],
        ]);
    }

    /**
     * @return list<SalesLineData>
     */
    protected function lineData(): array
    {
        return array_map(static fn (array $row): SalesLineData => new SalesLineData(
            itemId: (int) $row['item_id'],
            quantity: (string) $row['quantity'],
            unitPrice: (string) $row['unit_price'],
            itemVariantId: isset($row['item_variant_id']) ? (int) $row['item_variant_id'] : null,
            description: $row['description'] ?? null,
            uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
            sourceLineId: isset($row['source_line_id']) ? (int) $row['source_line_id'] : null,
            discountCalculationType: SalesAdjustmentCalculationType::from((string) ($row['discount_calculation_type'] ?? 'fixed')),
            discountRate: (string) ($row['discount_rate'] ?? '0.000000'),
            discountAmount: (string) ($row['discount_amount'] ?? '0.000000'),
            taxCalculationType: SalesAdjustmentCalculationType::from((string) ($row['tax_calculation_type'] ?? 'fixed')),
            taxRate: (string) ($row['tax_rate'] ?? '0.000000'),
            taxAmount: (string) ($row['tax_amount'] ?? '0.000000'),
            chargeCalculationType: SalesAdjustmentCalculationType::from((string) ($row['charge_calculation_type'] ?? 'fixed')),
            chargeRate: (string) ($row['charge_rate'] ?? '0.000000'),
            chargeAmount: (string) ($row['charge_amount'] ?? '0.000000'),
        ), $this->input('lines', []));
    }

    /**
     * @return list<SalesHeaderAdjustmentData>
     */
    protected function adjustmentData(): array
    {
        return array_map(static fn (array $row): SalesHeaderAdjustmentData => new SalesHeaderAdjustmentData(
            name: (string) $row['name'],
            adjustmentType: SalesAdjustmentType::from((string) $row['adjustment_type']),
            effect: SalesAdjustmentEffect::from((string) $row['effect']),
            amount: (string) ($row['amount'] ?? '0.000000'),
            calculationType: SalesAdjustmentCalculationType::from((string) ($row['calculation_type'] ?? 'fixed')),
            calculationBase: SalesAdjustmentCalculationBase::from((string) ($row['calculation_base'] ?? 'subtotal')),
            rate: (string) ($row['rate'] ?? '0.000000'),
            allocationMethod: SalesAdjustmentAllocationMethod::from((string) ($row['allocation_method'] ?? 'proportional')),
            isAllocatable: (bool) ($row['is_allocatable'] ?? true),
            sortOrder: (int) ($row['sort_order'] ?? 0),
            description: $row['description'] ?? null,
        ), $this->input('adjustments', []));
    }

}
