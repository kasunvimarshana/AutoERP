<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Role Resource
 *
 * Transform Role entity for API responses.
 */
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'level' => $this->level,
            'permissions' => $this->permissions,
            'is_system' => $this->is_system,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'parent' => $this->whenLoaded('parent', fn () => new RoleResource($this->parent)),
            'children' => $this->whenLoaded('children', fn () => RoleResource::collection($this->children)),
            'role_permissions' => $this->whenLoaded('rolePermissions', fn () => PermissionResource::collection($this->rolePermissions)),
            'users_count' => $this->when(isset($this->users_count), $this->users_count),

            // Computed
            'all_permissions' => $this->when(
                $request->boolean('include_all_permissions'),
                fn () => $this->getAllPermissions()
            ),
        ];
    }
}
