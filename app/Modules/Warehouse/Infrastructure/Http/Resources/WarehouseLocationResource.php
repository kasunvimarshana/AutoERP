<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'warehouse_id' => $this->getWarehouseId(),
            'parent_id' => $this->getParentId(),
            'name' => $this->getName(),
            'code' => $this->getCode(),
            'path' => $this->getPath(),
            'depth' => $this->getDepth(),
            'type' => $this->getType(),
            'is_active' => $this->isActive(),
            'is_pickable' => $this->isPickable(),
            'is_receivable' => $this->isReceivable(),
            'capacity' => $this->getCapacity(),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
