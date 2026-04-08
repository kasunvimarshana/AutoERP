<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->resource->id,
            'tenant_id'  => $this->resource->tenantId,
            'name'       => $this->resource->name,
            'code'       => $this->resource->code,
            'address'    => $this->resource->address,
            'is_active'  => $this->resource->isActive,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
