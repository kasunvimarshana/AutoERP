<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleServiceEmployeeAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'vehicle_service_job_line_id' => (int) $this->vehicle_service_job_line_id,
            'employee_id' => (int) $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => (int) $this->employee->getKey(),
                'code' => $this->employee->employee_number,
                'name' => $this->employee->display_name,
            ]),
            'role_type' => $this->role_type,
            'assigned_hours' => (string) $this->assigned_hours,
            'rate' => (string) $this->rate,
            'commission_type' => $this->commission_type instanceof \BackedEnum ? $this->commission_type->value : $this->commission_type,
            'commission_value' => (string) $this->commission_value,
            'commission_amount' => (string) $this->commission_amount,
            'status' => $this->status,
            'assigned_at' => $this->assigned_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
        ];
    }
}
