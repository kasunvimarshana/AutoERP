<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * OrganizationResource
 *
 * Formats organization data for API responses
 *
 * @mixin \Modules\Tenant\Models\Organization
 */
class OrganizationResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'level' => $this->level,
            'metadata' => $this->metadata ?? [],
            'is_active' => $this->is_active,
            'parent' => $this->whenLoaded('parent', fn () => new self($this->parent)),
            'children' => $this->whenLoaded('children', fn () => self::collection($this->children)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get the type label from config
     */
    protected function getTypeLabel(): string
    {
        $types = config('tenant.organizations.types', []);

        return $types[$this->type] ?? $this->type;
    }
}
