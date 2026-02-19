<?php

declare(strict_types=1);

namespace Modules\Customer\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * VehicleServiceRecord Resource
 *
 * Transforms VehicleServiceRecord model data for API responses
 */
class VehicleServiceRecordResource extends JsonResource
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
            'vehicle_id' => $this->vehicle_id,
            'customer_id' => $this->customer_id,
            'service_number' => $this->service_number,
            'branch_id' => $this->branch_id,
            'service_date' => $this->service_date?->format('Y-m-d'),
            'mileage_at_service' => $this->mileage_at_service,
            'service_type' => $this->service_type,
            'service_description' => $this->service_description,
            'parts_used' => $this->parts_used,
            'costs' => [
                'labor' => (float) $this->labor_cost,
                'parts' => (float) $this->parts_cost,
                'total' => (float) $this->total_cost,
            ],
            'technician' => [
                'id' => $this->technician_id,
                'name' => $this->technician_name,
            ],
            'notes' => $this->notes,
            'next_service' => [
                'mileage' => $this->next_service_mileage,
                'date' => $this->next_service_date?->format('Y-m-d'),
            ],
            'status' => $this->status,
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
