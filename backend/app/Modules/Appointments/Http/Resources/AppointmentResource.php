<?php

namespace App\Modules\Appointments\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'vehicle_id' => $this->vehicle_id,
            'service_bay_id' => $this->service_bay_id,
            'appointment_number' => $this->appointment_number,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'scheduled_time' => $this->scheduled_time,
            'duration_minutes' => $this->duration_minutes,
            'service_type' => $this->service_type,
            'status' => $this->status,
            'notes' => $this->notes,
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'customer' => $this->whenLoaded('customer'),
            'vehicle' => $this->whenLoaded('vehicle'),
            'service_bay' => $this->whenLoaded('serviceBay', function () {
                return new ServiceBayResource($this->serviceBay);
            }),
        ];
    }
}
