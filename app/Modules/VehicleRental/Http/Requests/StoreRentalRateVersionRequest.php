<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalExcessKmMethod;
use Modules\VehicleRental\Enums\RentalMode;
use Modules\VehicleRental\Enums\RentalProrationRule;
use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

final class StoreRentalRateVersionRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],
            'driver_mode' => ['nullable', Rule::enum(RentalMode::class)],
            'billing_cycle' => ['nullable', Rule::enum(RentalBillingCycle::class)],
            'billing_basis' => ['nullable', Rule::enum(RentalBillingBasis::class)],
            'proration_rule' => ['nullable', Rule::enum(RentalProrationRule::class)],
            'excess_km_method' => ['required', Rule::enum(RentalExcessKmMethod::class)],
            'included_km' => ['nullable', 'decimal:0,6', 'gte:0'],
            'included_hours' => ['nullable', 'decimal:0,6', 'gte:0'],
            'weekday_included_minutes' => ['nullable', 'integer', 'gte:0'],
            'saturday_included_minutes' => ['nullable', 'integer', 'gte:0'],
            'holiday_included_minutes' => ['nullable', 'integer', 'gte:0'],
            'currency_id' => ['nullable', 'integer', 'min:1'],
            'tax_group_id' => ['nullable', 'integer', 'min:1'],
            'withholding_tax_group_id' => ['nullable', 'integer', 'min:1'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.vehicle_category_id' => ['nullable', 'integer', 'min:1'],
            'components.*.component_code' => ['required', Rule::enum(RentalRateComponentCode::class)],
            'components.*.unit' => ['required', Rule::enum(RentalRateUnit::class)],
            'components.*.included_quantity' => ['nullable', 'decimal:0,6', 'gte:0'],
            'components.*.rate' => ['required', 'decimal:0,6', 'gte:0'],
            'components.*.multiplier' => ['nullable', 'decimal:0,6', 'gt:0'],
            'components.*.minimum_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'components.*.maximum_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'components.*.tax_group_override_id' => ['nullable', 'integer', 'min:1'],
            'components.*.is_taxable' => ['nullable', 'boolean'],
            'components.*.calculation_order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
