<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->getId(),
            'tenant_id'    => $this->getTenantId(),
            'warehouse_id' => $this->getWarehouseId(),
            'zone_id'      => $this->getZoneId(),
            'code'         => $this->getCode(),
            'name'         => $this->getName(),
            'type'         => $this->getType(),
            'aisle'        => $this->getAisle(),
            'row'          => $this->getRow(),
            'level'        => $this->getLevel(),
            'bin'          => $this->getBin(),
            'capacity'     => $this->getCapacity(),
            'weight_limit' => $this->getWeightLimit(),
            'barcode'      => $this->getBarcode(),
            'qr_code'      => $this->getQrCode(),
            'is_pickable'  => $this->isPickable(),
            'is_storable'  => $this->isStorable(),
            'is_packing'   => $this->isPacking(),
            'is_active'    => $this->isActive(),
            'metadata'     => $this->getMetadata()->toArray(),
            'created_at'   => $this->getCreatedAt()->format('c'),
            'updated_at'   => $this->getUpdatedAt()->format('c'),
        ];
    }
}
