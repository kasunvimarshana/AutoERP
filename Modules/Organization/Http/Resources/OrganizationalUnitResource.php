<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationalUnitResource extends JsonResource
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
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'location_id' => $this->location_id,
            'parent_unit_id' => $this->parent_unit_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'unit_type' => $this->unit_type,
            'status' => $this->status,
            'manager_id' => $this->manager_id,
            'email' => $this->email,
            'phone' => $this->phone,
            'settings' => $this->settings,
            'metadata' => $this->metadata,
            'level' => $this->level,
            'path' => $this->path,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Computed attributes
            'full_name' => $this->full_name,
            'is_active' => $this->isActive(),
            'is_root' => $this->isRoot(),
            'is_leaf' => $this->isLeaf(),
            
            // Relationships (when loaded)
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'parent' => new OrganizationalUnitResource($this->whenLoaded('parent')),
            'children' => OrganizationalUnitResource::collection($this->whenLoaded('children')),
            'manager' => $this->whenLoaded('manager', function () {
                return [
                    'id' => $this->manager->id,
                    'name' => $this->manager->name,
                    'email' => $this->manager->email,
                ];
            }),
        ];
    }
}
