<?php
declare(strict_types=1);
namespace Modules\Product\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'parent_id' => $this->resource->parentId,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'path' => $this->resource->path,
            'level' => $this->resource->level,
            'is_active' => $this->resource->isActive,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
