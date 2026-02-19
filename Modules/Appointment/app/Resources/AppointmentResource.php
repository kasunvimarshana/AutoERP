<?php

declare(strict_types=1);

namespace Modules\Appointment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Appointment Resource
 *
 * Transforms Appointment model data for API responses
 */
class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_number' => $this->appointment_number,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'full_name' => $this->customer->full_name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ]),
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'license_plate' => $this->vehicle->license_plate,
                'make' => $this->vehicle->make,
                'model' => $this->vehicle->model,
                'year' => $this->vehicle->year,
            ]),
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'service_type' => $this->service_type,
            'scheduled_date_time' => $this->scheduled_date_time?->format('Y-m-d H:i:s'),
            'duration' => $this->duration,
            'status' => $this->status,
            'notes' => $this->notes,
            'customer_notes' => $this->customer_notes,
            'assigned_technician_id' => $this->assigned_technician_id,
            'assigned_technician' => $this->whenLoaded('assignedTechnician', fn () => [
                'id' => $this->assignedTechnician->id,
                'name' => $this->assignedTechnician->name,
                'email' => $this->assignedTechnician->email,
            ]),
            'confirmed_at' => $this->confirmed_at?->format('Y-m-d H:i:s'),
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'bay_schedules' => BayScheduleResource::collection($this->whenLoaded('baySchedules')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
