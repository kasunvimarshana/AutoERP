<?php

declare(strict_types=1);

namespace Modules\JobCard\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Resources\CustomerResource;
use Modules\Customer\Resources\VehicleResource;
use Modules\Organization\Resources\BranchResource;

/**
 * JobCard Resource
 *
 * Transforms JobCard model data for API responses
 */
class JobCardResource extends JsonResource
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
            'job_number' => $this->job_number,
            'appointment_id' => $this->appointment_id,
            'vehicle_id' => $this->vehicle_id,
            'customer_id' => $this->customer_id,
            'branch_id' => $this->branch_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'technician_id' => $this->technician_id,
            'supervisor_id' => $this->supervisor_id,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'totals' => [
                'parts_total' => (float) $this->parts_total,
                'labor_total' => (float) $this->labor_total,
                'grand_total' => (float) $this->grand_total,
            ],
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'customer_complaints' => $this->customer_complaints,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'technician' => $this->whenLoaded('technician', function () {
                return [
                    'id' => $this->technician->id,
                    'name' => $this->technician->name,
                    'email' => $this->technician->email,
                ];
            }),
            'supervisor' => $this->whenLoaded('supervisor', function () {
                return [
                    'id' => $this->supervisor->id,
                    'name' => $this->supervisor->name,
                    'email' => $this->supervisor->email,
                ];
            }),
            'tasks' => JobTaskResource::collection($this->whenLoaded('tasks')),
            'inspection_items' => InspectionItemResource::collection($this->whenLoaded('inspectionItems')),
            'parts' => JobPartResource::collection($this->whenLoaded('parts')),
            'tasks_count' => $this->whenCounted('tasks'),
            'parts_count' => $this->whenCounted('parts'),
            'inspection_items_count' => $this->whenCounted('inspectionItems'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
