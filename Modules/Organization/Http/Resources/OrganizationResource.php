<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'parent_id' => $this->parent_id,
            'code' => $this->code,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'tax_id' => $this->tax_id,
            'registration_number' => $this->registration_number,
            'organization_type' => $this->organization_type,
            'status' => $this->status,
            'email' => $this->email,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'website' => $this->website,
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
            'parent' => $this->whenLoaded('parent', fn() => new OrganizationResource($this->parent)),
            'children' => $this->whenLoaded('children', fn() => OrganizationResource::collection($this->children)),
            'locations' => $this->whenLoaded('locations', fn() => LocationResource::collection($this->locations)),
        ];
    }
}
