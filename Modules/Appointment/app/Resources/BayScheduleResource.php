<?php

declare(strict_types=1);

namespace Modules\Appointment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BaySchedule Resource
 *
 * Transforms BaySchedule model data for API responses
 */
class BayScheduleResource extends JsonResource
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
            'bay_id' => $this->bay_id,
            'bay' => $this->whenLoaded('bay', fn () => [
                'id' => $this->bay->id,
                'bay_number' => $this->bay->bay_number,
                'bay_type' => $this->bay->bay_type,
            ]),
            'appointment_id' => $this->appointment_id,
            'appointment' => $this->whenLoaded('appointment', fn () => [
                'id' => $this->appointment->id,
                'appointment_number' => $this->appointment->appointment_number,
            ]),
            'start_time' => $this->start_time?->format('Y-m-d H:i:s'),
            'end_time' => $this->end_time?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
