<?php
namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;

class WarehouseLocationResource extends JsonResource
{
    public function __construct(private readonly WarehouseLocation $location)
    {
        parent::__construct($location);
    }

    public function toArray($request): array
    {
        return [
            'id'           => $this->location->id,
            'warehouseId'  => $this->location->warehouseId,
            'zoneId'       => $this->location->zoneId,
            'code'         => $this->location->code,
            'barcode'      => $this->location->barcode,
            'locationType' => $this->location->locationType,
            'isActive'     => $this->location->isActive,
            'aisle'        => $this->location->aisle,
            'bay'          => $this->location->bay,
            'level'        => $this->location->level,
            'bin'          => $this->location->bin,
            'maxWeight'    => $this->location->maxWeight,
            'maxVolume'    => $this->location->maxVolume,
        ];
    }
}
