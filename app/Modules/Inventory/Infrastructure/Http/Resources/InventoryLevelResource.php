<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLevelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->getId(),
            'tenant_id'      => $this->getTenantId(),
            'product_id'     => $this->getProductId(),
            'variation_id'   => $this->getVariationId(),
            'location_id'    => $this->getLocationId(),
            'batch_id'       => $this->getBatchId(),
            'uom_id'         => $this->getUomId(),
            'qty_on_hand'    => $this->getQtyOnHand(),
            'qty_reserved'   => $this->getQtyReserved(),
            'qty_available'  => $this->getQtyAvailable(),
            'qty_on_order'   => $this->getQtyOnOrder(),
            'reorder_point'  => $this->getReorderPoint(),
            'reorder_qty'    => $this->getReorderQty(),
            'max_qty'        => $this->getMaxQty(),
            'min_qty'        => $this->getMinQty(),
            'is_low_stock'   => $this->isLowStock(),
            'last_counted_at'=> $this->getLastCountedAt()?->format('c'),
            'created_at'     => $this->getCreatedAt()->format('c'),
            'updated_at'     => $this->getUpdatedAt()->format('c'),
        ];
    }
}
