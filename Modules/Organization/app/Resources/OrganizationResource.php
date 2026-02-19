<?php

declare(strict_types=1);

namespace Modules\Organization\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Organization Resource
 *
 * Transforms Organization model for API responses
 */
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
            'organization_number' => $this->organization_number,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'type' => [
                'value' => is_object($this->type) ? $this->type->value : $this->type,
                'label' => is_object($this->type) ? $this->type->label() : ($this->type ? ucwords(str_replace('_', ' ', $this->type)) : ''),
                'description' => is_object($this->type) ? $this->type->description() : '',
            ],
            'status' => [
                'value' => is_object($this->status) ? $this->status->value : $this->status,
                'label' => is_object($this->status) ? $this->status->label() : ($this->status ? ucfirst($this->status) : ''),
                'description' => is_object($this->status) ? $this->status->description() : '',
                'color' => is_object($this->status) ? $this->status->color() : 'default',
            ],
            'tax_id' => $this->tax_id,
            'registration_number' => $this->registration_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => [
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],
            'metadata' => $this->metadata,
            'branches_count' => $this->when(
                $this->relationLoaded('branches'),
                fn () => $this->branches->count()
            ),
            'active_branches_count' => $this->when(
                $this->relationLoaded('branches'),
                fn () => $this->branches->where('status', 'active')->count()
            ),
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            'is_active' => $this->isActive(),
            'has_multiple_branches' => $this->hasMultipleBranches(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
