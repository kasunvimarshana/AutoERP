<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseZoneResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->getId(),
            'warehouse_id'   => $this->getWarehouseId(),
            'tenant_id'      => $this->getTenantId(),
            'name'           => $this->getName()->value(),
            'type'           => $this->getType(),
            'code'           => $this->getCode()?->value(),
            'description'    => $this->getDescription(),
            'capacity'       => $this->getCapacity(),
            'metadata'       => $this->getMetadata()?->toArray() ?? [],
            'is_active'      => $this->isActive(),
            'parent_zone_id' => $this->getParentZoneId(),
            'children'       => WarehouseZoneResource::collection($this->getChildren()),
            'created_at'     => $this->getCreatedAt()->format('c'),
            'updated_at'     => $this->getUpdatedAt()->format('c'),
        ];
    }
}
