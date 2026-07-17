<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalExcessKmMethod;
use Modules\VehicleRental\Enums\RentalMode;
use Modules\VehicleRental\Enums\RentalProrationRule;
use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

class StoreRentalAgreementRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'agreement_number' => ['nullable', 'string', 'max:100'],
            'agreement_kind' => ['required', Rule::enum(RentalAgreementKind::class)],
            'reservation_id' => ['nullable', 'integer', 'min:1'],
            'expected_reservation_version' => ['nullable', 'required_with:reservation_id', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'required_if:agreement_kind,customer_rental', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'required_if:agreement_kind,owner_supply', 'integer', 'min:1'],
            'agreement_date' => ['required', 'date'],
            'executed_at' => ['nullable', 'date', 'after_or_equal:agreement_date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'legal_context' => ['required', Rule::in(['company', 'personal'])],
            'rental_mode' => ['required', Rule::enum(RentalMode::class)],
            'billing_cycle' => ['required', Rule::enum(RentalBillingCycle::class)],
            'billing_basis' => ['required', Rule::enum(RentalBillingBasis::class)],
            'proration_rule' => ['required', Rule::enum(RentalProrationRule::class)],
            'billing_timezone' => ['nullable', 'timezone'],
            'payment_term_days' => ['nullable', 'integer', 'between:0,3650'],
            'currency_id' => ['required', 'integer', 'min:1'],
            'remarks' => ['nullable', 'string'],
            'terms' => ['nullable', 'array', 'max:50'],
            'terms.*.id' => ['nullable', 'integer', 'min:1'],
            'terms.*.sequence' => ['nullable', 'integer', 'min:1'],
            'terms.*.term_code' => ['nullable', 'string', 'max:50'],
            'terms.*.title' => ['nullable', 'string', 'max:150'],
            'terms.*.content' => ['nullable', 'string', 'max:20000'],
            'terms.*.is_printable' => ['nullable', 'boolean'],
            'activate_rate_version' => ['nullable', 'boolean'],
            'rate_version' => ['nullable', 'array'],
            'rate_version.id' => ['nullable', 'integer', 'min:1'],
            'rate_version.expected_version' => ['nullable', 'integer', 'min:1'],
            'rate_version.effective_from' => ['nullable', 'date'],
            'rate_version.effective_to' => ['nullable', 'date', 'after:rate_version.effective_from'],
            'rate_version.driver_mode' => ['nullable', Rule::enum(RentalMode::class)],
            'rate_version.billing_cycle' => ['nullable', Rule::enum(RentalBillingCycle::class)],
            'rate_version.billing_basis' => ['nullable', Rule::enum(RentalBillingBasis::class)],
            'rate_version.proration_rule' => ['nullable', Rule::enum(RentalProrationRule::class)],
            'rate_version.excess_km_method' => ['required_with:rate_version', Rule::enum(RentalExcessKmMethod::class)],
            'rate_version.included_km' => ['nullable', 'decimal:0,6', 'gte:0'],
            'rate_version.included_hours' => ['nullable', 'decimal:0,6', 'gte:0'],
            'rate_version.weekday_included_minutes' => ['nullable', 'integer', 'gte:0'],
            'rate_version.saturday_included_minutes' => ['nullable', 'integer', 'gte:0'],
            'rate_version.holiday_included_minutes' => ['nullable', 'integer', 'gte:0'],
            'rate_version.currency_id' => ['nullable', 'integer', 'min:1'],
            'rate_version.tax_group_id' => ['nullable', 'integer', 'min:1'],
            'rate_version.withholding_tax_group_id' => ['nullable', 'integer', 'min:1'],
            'rate_version.components' => ['required_with:rate_version', 'array', 'min:1'],
            'rate_version.components.*.vehicle_category_id' => ['nullable', 'integer', 'min:1'],
            'rate_version.components.*.component_code' => ['required', Rule::enum(RentalRateComponentCode::class)],
            'rate_version.components.*.unit' => ['required', Rule::enum(RentalRateUnit::class)],
            'rate_version.components.*.included_quantity' => ['nullable', 'decimal:0,6', 'gte:0'],
            'rate_version.components.*.rate' => ['required', 'decimal:0,6', 'gte:0'],
            'rate_version.components.*.multiplier' => ['nullable', 'decimal:0,6', 'gt:0'],
            'rate_version.components.*.minimum_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'rate_version.components.*.maximum_amount' => ['nullable', 'decimal:0,6', 'gte:0'],
            'rate_version.components.*.tax_group_override_id' => ['nullable', 'integer', 'min:1'],
            'rate_version.components.*.is_taxable' => ['nullable', 'boolean'],
            'rate_version.components.*.calculation_order' => ['nullable', 'integer', 'min:1'],
            'deposit' => ['nullable', 'prohibited_unless:agreement_kind,'.RentalAgreementKind::CustomerRental->value, 'array'],
            'deposit.required_amount' => ['required_with:deposit', 'decimal:0,6', 'gte:0'],
            'deposit.currency_id' => ['nullable', 'integer', 'min:1'],
            'deposit.due_date' => ['nullable', 'date'],
            'deposit.is_refundable' => ['nullable', 'boolean'],
            'deposit.remarks' => ['nullable', 'string'],
        ];
    }
}
