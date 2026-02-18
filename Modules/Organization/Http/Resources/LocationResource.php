<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'parent_location_id' => $this->parent_location_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'location_type' => $this->location_type,
            'status' => $this->status,
            'contact' => [
                'email' => $this->email,
                'phone' => $this->phone,
                'fax' => $this->fax,
                'contact_person' => $this->contact_person,
            ],
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'full_address' => $this->full_address,
            ],
            'coordinates' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'operating_hours' => $this->operating_hours,
            'timezone' => $this->timezone,
            'capacity' => [
                'area_sqm' => $this->area_sqm,
                'capacity' => $this->capacity,
            ],
            'hierarchy' => [
                'level' => $this->level,
                'path' => $this->path,
                'full_name' => $this->full_name,
                'is_root' => $this->isRoot(),
                'is_leaf' => $this->isLeaf(),
            ],
            'settings' => $this->settings,
            'features' => $this->features,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relationships
            'organization' => $this->whenLoaded('organization', fn() => new OrganizationResource($this->organization)),
            'parent' => $this->whenLoaded('parent', fn() => new LocationResource($this->parent)),
            'children' => $this->whenLoaded('children', fn() => LocationResource::collection($this->children)),
        ];
    }
}
