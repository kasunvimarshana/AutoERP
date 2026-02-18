<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Group Resource
 *
 * Transforms group data for API responses.
 */
class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Relationships (loaded conditionally)
            'parent' => new GroupResource($this->whenLoaded('parent')),
            'children' => GroupResource::collection($this->whenLoaded('children')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            
            // Computed attributes
            'users_count' => $this->when(
                $this->relationLoaded('users'),
                fn() => $this->users->count()
            ),
            'roles_count' => $this->when(
                $this->relationLoaded('roles'),
                fn() => $this->roles->count()
            ),
            'children_count' => $this->when(
                $this->relationLoaded('children'),
                fn() => $this->children->count()
            ),
        ];
    }
}
