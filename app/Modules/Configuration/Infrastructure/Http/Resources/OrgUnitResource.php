<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrgUnitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->resource->id,
            'tenant_id'  => $this->resource->tenantId,
            'name'       => $this->resource->name,
            'type'       => $this->resource->type,
            'code'       => $this->resource->code,
            'parent_id'  => $this->resource->parentId,
            'path'       => $this->resource->path,
            'level'      => $this->resource->level,
            'is_active'  => $this->resource->isActive,
            'metadata'   => $this->resource->metadata,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
