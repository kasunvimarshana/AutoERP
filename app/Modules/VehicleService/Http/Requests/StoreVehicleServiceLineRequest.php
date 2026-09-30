<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleService\DTOs\VehicleServiceLineData;
use Modules\VehicleService\Enums\VehicleServiceLineSourceType;
use Modules\VehicleService\Http\Requests\Concerns\HasExpectedVehicleServiceJobVersion;
use Modules\VehicleService\Http\Requests\Concerns\NormalizesBooleanInput;

final class StoreVehicleServiceLineRequest extends TenantScopedRequest
{
    use HasExpectedVehicleServiceJobVersion;
    use NormalizesBooleanInput;

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $this->expectedVersionRules(),
            'parent_line_id' => ['nullable', 'integer', 'min:1'],
            'line_source_type' => ['required', Rule::enum(VehicleServiceLineSourceType::class)],
            'item_id' => ['nullable', 'integer', 'min:1'],
            'item_variant_id' => ['nullable', 'integer', 'min:1'],
            'batch_id' => ['nullable', 'integer', 'min:1'],
            'batch_price_revision_id' => ['nullable', 'integer', 'min:1'],
            'uom_id' => ['nullable', 'integer', 'min:1'],
            'description' => ['required', 'string'],
            'quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'unit_cost' => ['nullable', 'decimal:0,6', 'min:0'],
            'unit_price' => ['required', 'decimal:0,6', 'min:0'],
            'discount_calculation_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'discount_rate' => ['nullable', 'decimal:0,6', 'between:0,100'],
            'discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'tax_calculation_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'tax_rate' => ['nullable', 'decimal:0,6', 'between:0,100'],
            'tax_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'charge_calculation_type' => ['nullable', Rule::in(['fixed', 'percentage'])],
            'charge_rate' => ['nullable', 'decimal:0,6', 'between:0,100'],
            'charge_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'is_customer_supplied' => ['nullable', 'boolean'],
            'is_billable' => ['nullable', 'boolean'],
            'expand_combo' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): VehicleServiceLineData
    {
        return new VehicleServiceLineData(
            lineSourceType: VehicleServiceLineSourceType::from((string) $this->input('line_source_type')),
            description: (string) $this->input('description'),
            quantity: (string) $this->input('quantity'),
            unitPrice: (string) $this->input('unit_price'),
            parentLineId: $this->intOrNull('parent_line_id'),
            itemId: $this->intOrNull('item_id'),
            itemVariantId: $this->intOrNull('item_variant_id'),
            batchId: $this->intOrNull('batch_id'),
            batchPriceRevisionId: $this->intOrNull('batch_price_revision_id'),
            uomId: $this->intOrNull('uom_id'),
            unitCost: (string) $this->input('unit_cost', '0.000000'),
            discountCalculationType: $this->stringOrNull('discount_calculation_type'),
            discountRate: (string) $this->input('discount_rate', '0.000000'),
            discountAmount: (string) $this->input('discount_amount', '0.000000'),
            taxCalculationType: $this->stringOrNull('tax_calculation_type'),
            taxRate: (string) $this->input('tax_rate', '0.000000'),
            taxAmount: (string) $this->input('tax_amount', '0.000000'),
            chargeCalculationType: $this->stringOrNull('charge_calculation_type'),
            chargeRate: (string) $this->input('charge_rate', '0.000000'),
            chargeAmount: (string) $this->input('charge_amount', '0.000000'),
            isCustomerSupplied: $this->boolean('is_customer_supplied'),
            isBillable: $this->has('is_billable') ? $this->boolean('is_billable') : ! $this->boolean('is_customer_supplied'),
            expandCombo: ! $this->has('expand_combo') || $this->boolean('expand_combo'),
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
