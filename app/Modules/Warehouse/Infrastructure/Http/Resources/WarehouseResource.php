<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->getId(),
            'tenant_id'   => $this->getTenantId(),
            'name'        => $this->getName()->value(),
            'type'        => $this->getType(),
            'code'        => $this->getCode()?->value(),
            'description' => $this->getDescription(),
            'address'     => $this->getAddress(),
            'capacity'    => $this->getCapacity(),
            'location_id' => $this->getLocationId(),
            'metadata'    => $this->getMetadata()?->toArray() ?? [],
            'is_active'   => $this->isActive(),
            'created_at'  => $this->getCreatedAt()->format('c'),
            'updated_at'  => $this->getUpdatedAt()->format('c'),
        ];
    }
}
