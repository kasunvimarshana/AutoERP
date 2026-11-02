<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpdateRentalUsageFactRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:1'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after:started_at'],
            'start_odometer' => ['required', 'decimal:0,6', 'gte:0'],
            'end_odometer' => ['required', 'decimal:0,6', 'gte:start_odometer'],
            'commercial_distance_km' => ['required', 'decimal:0,6', 'gte:0'],
            'normal_overtime_minutes' => ['nullable', 'integer', 'gte:0'],
            'double_overtime_minutes' => ['nullable', 'integer', 'gte:0'],
            'triple_overtime_minutes' => ['nullable', 'integer', 'gte:0'],
            'night_out_count' => ['nullable', 'decimal:0,6', 'gte:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'variance_reason' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
