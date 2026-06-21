<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalUsageEventType;

final class StoreRentalUsageRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'usage_date' => ['required', 'date'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after:started_at'],
            'start_odometer' => ['required', 'decimal:0,6', 'gte:0'],
            'end_odometer' => ['required', 'decimal:0,6', 'gte:start_odometer'],
            'garage_distance_km' => ['nullable', 'decimal:0,6', 'gte:0'],
            'internal_distance_km' => ['nullable', 'decimal:0,6', 'gte:0'],
            'working_minutes' => ['nullable', 'integer', 'gte:0'],
            'normal_overtime_minutes' => ['nullable', 'integer', 'gte:0'],
            'double_overtime_minutes' => ['nullable', 'integer', 'gte:0'],
            'triple_overtime_minutes' => ['nullable', 'integer', 'gte:0'],
            'night_out_count' => ['nullable', 'decimal:0,6', 'gte:0'],
            'driver_assignment_id' => ['nullable', 'integer', 'min:1'],
            'driver_id' => ['nullable', 'integer', 'min:1'],
            'trip_from' => ['nullable', 'string', 'max:255'],
            'trip_to' => ['nullable', 'string', 'max:255'],
            'trip_purpose' => ['nullable', 'string', 'max:255'],
            'odometer_variance_reason' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'events' => ['nullable', 'array'],
            'events.*.event_type' => ['required', Rule::enum(RentalUsageEventType::class)],
            'events.*.occurred_at' => ['nullable', 'date'],
            'events.*.quantity' => ['required', 'decimal:0,6', 'gte:0'],
            'events.*.unit' => ['nullable', 'string', 'max:30'],
            'events.*.reference_number' => ['nullable', 'string', 'max:100'],
            'events.*.remarks' => ['nullable', 'string'],
        ];
    }
}
