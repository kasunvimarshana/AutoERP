<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
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
            'module' => $this->module,
            'resource' => $this->resource,
            'action' => $this->action,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'is_active' => $this->is_active,
            'full_identifier' => $this->full_identifier,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
