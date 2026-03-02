<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\InventoryLot;

class LotResource extends JsonResource
{
    /** @var InventoryLot */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'product_id' => $this->resource->productId,
            'warehouse_id' => $this->resource->warehouseId,
            'lot_number' => $this->resource->lotNumber,
            'serial_number' => $this->resource->serialNumber,
            'batch_number' => $this->resource->batchNumber,
            'manufactured_date' => $this->resource->manufacturedDate,
            'expiry_date' => $this->resource->expiryDate,
            'quantity' => $this->resource->quantity,
            'notes' => $this->resource->notes,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
