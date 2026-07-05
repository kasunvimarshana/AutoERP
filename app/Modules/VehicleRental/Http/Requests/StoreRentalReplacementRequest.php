<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalVehicleSourceType;

final class StoreRentalReplacementRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_allocation_version' => ['required', 'integer', 'min:1'],
            'expected_agreement_version' => ['required', 'integer', 'min:1'],
            'new_vehicle_id' => ['required', 'integer', 'min:1'],
            'vehicle_ownership_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_source_type' => ['required', Rule::enum(RentalVehicleSourceType::class)],
            'source_allocation_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_finance_agreement_id' => ['nullable', 'integer', 'min:1'],
            'replacement_at' => ['required', 'date'],
            'allocated_to' => ['nullable', 'date', 'after:replacement_at'],
            'reason_code' => ['nullable', 'string', 'max:50'],
            'reason' => ['nullable', 'string'],
            'billing_continuity_rule' => ['nullable', Rule::in(['continue_period', 'split_period'])],
            'remarks' => ['nullable', 'string'],
            'drivers' => ['nullable', 'array'],
            'drivers.*.employee_id' => ['required', 'integer', 'min:1'],
            'drivers.*.assignment_role' => ['nullable', Rule::in(['primary', 'relief'])],
            'drivers.*.assigned_from' => ['nullable', 'date'],
            'drivers.*.assigned_to' => ['nullable', 'date'],
            'drivers.*.is_primary' => ['nullable', 'boolean'],
            'old_return' => ['required', 'array'],
            'old_return.odometer' => ['required', 'decimal:0,6', 'gte:0'],
            'old_return.fuel_level_percent' => ['nullable', 'decimal:0,4', 'between:0,100'],
            'old_return.location' => ['nullable', 'string', 'max:255'],
            'old_return.condition_summary' => ['nullable', 'string'],
            'old_return.damage_summary' => ['nullable', 'string'],
            'old_return.items' => ['nullable', 'array'],
            'new_handover' => ['required', 'array'],
            'new_handover.odometer' => ['required', 'decimal:0,6', 'gte:0'],
            'new_handover.fuel_level_percent' => ['nullable', 'decimal:0,4', 'between:0,100'],
            'new_handover.location' => ['nullable', 'string', 'max:255'],
            'new_handover.condition_summary' => ['nullable', 'string'],
            'new_handover.damage_summary' => ['nullable', 'string'],
            'new_handover.items' => ['nullable', 'array'],
        ];
    }
}
