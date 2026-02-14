<?php

namespace App\Modules\JobCards\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class JobCardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'appointment_id' => $this->appointment_id,
            'customer_id' => $this->customer_id,
            'vehicle_id' => $this->vehicle_id,
            'job_card_number' => $this->job_card_number,
            'opened_date' => $this->opened_date->toIso8601String(),
            'closed_date' => $this->closed_date ? $this->closed_date->toIso8601String() : null,
            'status' => $this->status,
            'technician_id' => $this->technician_id,
            'estimated_cost' => number_format((float) $this->estimated_cost, 2, '.', ''),
            'actual_cost' => number_format((float) $this->actual_cost, 2, '.', ''),
            'notes' => $this->notes,
            'tasks' => $this->whenLoaded('tasks', function () {
                return JobCardTaskResource::collection($this->tasks);
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                ];
            }),
            'vehicle' => $this->whenLoaded('vehicle', function () {
                return [
                    'id' => $this->vehicle->id,
                    'license_plate' => $this->vehicle->license_plate,
                    'make' => $this->vehicle->make,
                    'model' => $this->vehicle->model,
                    'year' => $this->vehicle->year,
                ];
            }),
            'technician' => $this->whenLoaded('technician', function () {
                return $this->technician ? [
                    'id' => $this->technician->id,
                    'name' => $this->technician->name,
                    'email' => $this->technician->email,
                ] : null;
            }),
            'appointment' => $this->whenLoaded('appointment', function () {
                return $this->appointment ? [
                    'id' => $this->appointment->id,
                    'appointment_date' => $this->appointment->appointment_date,
                    'status' => $this->appointment->status,
                ] : null;
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
