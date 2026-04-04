<?php
namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\InventoryLevel;

class InventoryLevelResource extends JsonResource
{
    public function __construct(private readonly InventoryLevel $level)
    {
        parent::__construct($level);
    }

    public function toArray($request): array
    {
        return [
            'id'                 => $this->level->id,
            'tenant_id'          => $this->level->tenantId,
            'product_id'         => $this->level->productId,
            'warehouse_id'       => $this->level->warehouseId,
            'location_id'        => $this->level->locationId,
            'quantity_on_hand'   => $this->level->quantityOnHand,
            'quantity_reserved'  => $this->level->quantityReserved,
            'quantity_available' => $this->level->quantityAvailable,
            'quantity_on_order'  => $this->level->quantityOnOrder,
            'batch_id'           => $this->level->batchId,
            'lot_id'             => $this->level->lotId,
            'serial_id'          => $this->level->serialId,
            'stock_status'       => $this->level->stockStatus,
        ];
    }
}
