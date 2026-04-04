<?php
namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Warehouse\Domain\Entities\Warehouse;

class WarehouseResource extends JsonResource
{
    public function __construct(private readonly Warehouse $warehouse)
    {
        parent::__construct($warehouse);
    }

    public function toArray($request): array
    {
        return [
            'id'        => $this->warehouse->id,
            'tenantId'  => $this->warehouse->tenantId,
            'code'      => $this->warehouse->code,
            'name'      => $this->warehouse->name,
            'type'      => $this->warehouse->type,
            'status'    => $this->warehouse->status,
            'address'   => $this->warehouse->address,
            'city'      => $this->warehouse->city,
            'country'   => $this->warehouse->country,
            'isDefault' => $this->warehouse->isDefault,
        ];
    }
}
