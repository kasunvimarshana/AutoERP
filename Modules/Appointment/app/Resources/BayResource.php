<?php

declare(strict_types=1);

namespace Modules\Appointment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bay Resource
 *
 * Transforms Bay model data for API responses
 */
class BayResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'bay_number' => $this->bay_number,
            'bay_type' => $this->bay_type,
            'status' => $this->status,
            'capacity' => $this->capacity,
            'notes' => $this->notes,
            'schedules_count' => $this->whenCounted('schedules'),
            'schedules' => BayScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
