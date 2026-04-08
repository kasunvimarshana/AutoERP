<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseLocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->resource->id,
            'tenant_id'    => $this->resource->tenantId,
            'warehouse_id' => $this->resource->warehouseId,
            'parent_id'    => $this->resource->parentId,
            'name'         => $this->resource->name,
            'code'         => $this->resource->code,
            'path'         => $this->resource->path,
            'level'        => $this->resource->level,
            'type'         => $this->resource->type,
            'is_active'    => $this->resource->isActive,
            'created_at'   => $this->resource->createdAt,
            'updated_at'   => $this->resource->updatedAt,
        ];
    }
}
