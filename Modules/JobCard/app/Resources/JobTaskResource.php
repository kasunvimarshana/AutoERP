<?php

declare(strict_types=1);

namespace Modules\JobCard\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JobTask Resource
 *
 * Transforms JobTask model data for API responses
 */
class JobTaskResource extends JsonResource
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
            'job_card_id' => $this->job_card_id,
            'task_description' => $this->task_description,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'estimated_time' => $this->estimated_time,
            'actual_time' => $this->actual_time,
            'notes' => $this->notes,
            'assigned_user' => $this->whenLoaded('assignedUser', function () {
                return [
                    'id' => $this->assignedUser->id,
                    'name' => $this->assignedUser->name,
                    'email' => $this->assignedUser->email,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
