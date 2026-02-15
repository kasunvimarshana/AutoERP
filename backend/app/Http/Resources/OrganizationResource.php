<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'legal_name' => $this->legal_name,
            'tax_id' => $this->tax_id,
            'registration_number' => $this->registration_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'website' => $this->website,
            'logo' => $this->logo,
            'type' => $this->type,
            'status' => $this->status,
            'settings' => $this->settings,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Computed properties
            'is_active' => $this->status === 'active',
            'is_headquarters' => $this->type === 'headquarters',
            'has_parent' => $this->parent_id !== null,
            'logo_url' => $this->logo ? url("storage/{$this->logo}") : null,
            
            // Related data (when loaded)
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'uuid' => $this->tenant->uuid,
                    'name' => $this->tenant->name,
                    'slug' => $this->tenant->slug,
                ];
            }),
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent->id,
                    'uuid' => $this->parent->uuid,
                    'name' => $this->parent->name,
                    'type' => $this->parent->type,
                ];
            }),
            'children' => $this->whenLoaded('children', function () {
                return self::collection($this->children);
            }),
            'branches' => $this->whenLoaded('branches', function () {
                return BranchResource::collection($this->branches);
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
            'branches_count' => $this->when(
                isset($this->branches_count),
                $this->branches_count
            ),
            'children_count' => $this->when(
                isset($this->children_count),
                $this->children_count
            ),
        ];
    }
}
