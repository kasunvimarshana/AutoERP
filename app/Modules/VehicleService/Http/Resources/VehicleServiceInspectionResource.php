<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleServiceInspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'customer_complaint' => $this->customer_complaint,
            'inspection_notes' => $this->inspection_notes,
            'diagnosis' => $this->diagnosis,
            'recommended_work' => $this->recommended_work,
            'odometer_reading' => $this->odometer_reading === null ? null : (string) $this->odometer_reading,
            'fuel_level' => $this->fuel_level,
            'inspected_by' => $this->whenLoaded('inspector', fn () => $this->employee($this->inspector)),
            'inspected_at' => $this->inspected_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function employee(mixed $employee): ?array
    {
        return $employee === null ? null : [
            'id' => (int) $employee->getKey(),
            'code' => $employee->employee_number,
            'name' => $employee->display_name,
        ];
    }
}
