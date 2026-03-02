<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\StockBalance;

class StockBalanceResource extends JsonResource
{
    /** @var StockBalance */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'warehouse_id' => $this->resource->warehouseId,
            'product_id' => $this->resource->productId,
            'quantity_on_hand' => $this->resource->quantityOnHand,
            'quantity_reserved' => $this->resource->quantityReserved,
            'quantity_available' => $this->resource->availableQuantity(),
            'average_cost' => $this->resource->averageCost,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
