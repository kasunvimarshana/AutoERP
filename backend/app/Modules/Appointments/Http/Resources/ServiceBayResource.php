<?php

namespace App\Modules\Appointments\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceBayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'bay_number' => $this->bay_number,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'current_appointment_id' => $this->current_appointment_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'current_appointment' => $this->whenLoaded('currentAppointment', function () {
                return new AppointmentResource($this->currentAppointment);
            }),
        ];
    }
}
