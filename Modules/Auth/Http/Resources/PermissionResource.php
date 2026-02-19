<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PermissionResource
 *
 * Formats permission data for API responses
 *
 * @mixin \Modules\Auth\Models\Permission
 */
class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'resource' => $this->resource,
            'action' => $this->action,
            'is_system' => $this->is_system,
            'metadata' => $this->metadata ?? [],
            'roles_count' => $this->when(
                $this->relationLoaded('roles'),
                fn () => $this->roles->count()
            ),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
