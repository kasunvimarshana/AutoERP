<?php
namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Warehouse\Domain\Entities\WarehouseZone;

class WarehouseZoneResource extends JsonResource
{
    public function __construct(private readonly WarehouseZone $zone)
    {
        parent::__construct($zone);
    }

    public function toArray($request): array
    {
        return [
            'id'          => $this->zone->id,
            'warehouseId' => $this->zone->warehouseId,
            'code'        => $this->zone->code,
            'name'        => $this->zone->name,
            'type'        => $this->zone->type,
            'status'      => $this->zone->status,
            'description' => $this->zone->description,
        ];
    }
}
