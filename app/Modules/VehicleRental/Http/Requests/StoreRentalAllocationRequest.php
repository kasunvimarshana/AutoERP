<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalVehicleSourceType;

final class StoreRentalAllocationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'integer', 'min:1'],
            'vehicle_ownership_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_source_type' => ['required', Rule::enum(RentalVehicleSourceType::class)],
            'source_allocation_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_finance_agreement_id' => ['nullable', 'integer', 'min:1'],
            'replaces_allocation_id' => ['nullable', 'integer', 'min:1'],
            'allocated_from' => ['required', 'date'],
            'allocated_to' => ['nullable', 'date', 'after:allocated_from'],
            'start_odometer' => ['nullable', 'decimal:0,6', 'gte:0'],
            'remarks' => ['nullable', 'string'],
            'drivers' => ['nullable', 'array'],
            'drivers.*.employee_id' => ['required', 'integer', 'min:1'],
            'drivers.*.assignment_role' => ['nullable', Rule::in(['primary', 'relief'])],
            'drivers.*.assigned_from' => ['nullable', 'date'],
            'drivers.*.assigned_to' => ['nullable', 'date'],
            'drivers.*.is_primary' => ['nullable', 'boolean'],
            'drivers.*.remarks' => ['nullable', 'string'],
        ];
    }
}
