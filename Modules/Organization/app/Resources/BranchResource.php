<?php

declare(strict_types=1);

namespace Modules\Organization\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Branch Resource
 *
 * Transforms Branch model for API responses
 */
class BranchResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'branch_code' => $this->branch_code,
            'name' => $this->name,
            'status' => [
                'value' => is_object($this->status) ? $this->status->value : $this->status,
                'label' => is_object($this->status) ? $this->status->label() : ($this->status ? ucfirst($this->status) : ''),
                'description' => is_object($this->status) ? $this->status->description() : '',
                'color' => is_object($this->status) ? $this->status->color() : 'default',
            ],
            'manager_name' => $this->manager_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => [
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'full_address' => $this->getFullAddress(),
            ],
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'has_coordinates' => $this->hasGPSCoordinates(),
            ],
            'operating_hours' => $this->operating_hours,
            'services_offered' => $this->services_offered,
            'capacity' => [
                'vehicles' => $this->capacity_vehicles,
                'bays' => $this->bay_count,
            ],
            'metadata' => $this->metadata,
            'is_active' => $this->isActive(),
            'is_under_maintenance' => $this->isUnderMaintenance(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
