<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'branch_id' => $this->branch_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'capacity' => $this->capacity,
            'barcode' => $this->barcode,
            'is_pickable' => $this->is_pickable,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Computed properties
            'has_capacity' => $this->capacity !== null,
            'has_parent' => $this->parent_id !== null,
            'full_path' => $this->when(
                method_exists($this, 'getFullPath'),
                fn() => $this->getFullPath()
            ),
            
            // Related data (when loaded)
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'uuid' => $this->tenant->uuid,
                    'name' => $this->tenant->name,
                ];
            }),
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'uuid' => $this->branch->uuid,
                    'name' => $this->branch->name,
                    'code' => $this->branch->code,
                ];
            }),
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent->id,
                    'uuid' => $this->parent->uuid,
                    'name' => $this->parent->name,
                    'code' => $this->parent->code,
                    'type' => $this->parent->type,
                ];
            }),
            'children' => $this->whenLoaded('children', function () {
                return self::collection($this->children);
            }),
            'created_by_user' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'updated_by_user' => $this->whenLoaded('updatedBy', function () {
                return [
                    'id' => $this->updatedBy->id,
                    'name' => $this->updatedBy->name,
                    'email' => $this->updatedBy->email,
                ];
            }),
            'children_count' => $this->when(
                isset($this->children_count),
                $this->children_count
            ),
        ];
    }
}
