<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CostCenterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'parent_id' => $this->resource->getParentId(),
            'code' => $this->resource->getCode(),
            'name' => $this->resource->getName(),
            'description' => $this->resource->getDescription(),
            'is_active' => $this->resource->isActive(),
            'path' => $this->resource->getPath(),
            'depth' => $this->resource->getDepth(),
            'row_version' => $this->resource->getRowVersion(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
