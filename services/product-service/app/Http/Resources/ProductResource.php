<?php

namespace App\Http\Resources;

use App\DTOs\InventoryDataDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @var InventoryDataDTO|null Injected by ProductController after fetch
     */
    private ?InventoryDataDTO $inventoryData = null;

    public function withInventoryData(?InventoryDataDTO $inventoryData): self
    {
        $this->inventoryData = $inventoryData;
        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'tenant_id'   => $this->tenant_id,
            'sku'         => $this->sku,
            'name'        => $this->name,
            'description' => $this->description,
            'price'       => (float) $this->price,
            'cost_price'  => $this->cost_price ? (float) $this->cost_price : null,
            'currency'    => $this->currency,
            'unit'        => $this->unit,
            'weight'      => $this->weight ? (float) $this->weight : null,
            'dimensions'  => $this->dimensions,
            'status'      => $this->status,
            'is_active'   => (bool) $this->is_active,
            'tags'        => $this->tags ?? [],
            'images'      => $this->images ?? [],
            'metadata'    => $this->metadata,
            'category'    => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'category_id' => $this->category_id,
            'inventory'   => $this->resolveInventory(),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }

    private function resolveInventory(): array|null
    {
        if ($this->inventoryData === null) {
            return null;
        }

        return [
            'quantity_on_hand'   => $this->inventoryData->quantityOnHand,
            'quantity_reserved'  => $this->inventoryData->quantityReserved,
            'quantity_available' => $this->inventoryData->quantityAvailable,
            'warehouse_id'       => $this->inventoryData->warehouseId,
            'location'           => $this->inventoryData->location,
            'tracked'            => $this->inventoryData->tracked,
            'reorder_point'      => $this->inventoryData->reorderPoint,
            'reorder_quantity'   => $this->inventoryData->reorderQuantity,
            'synced_at'          => $this->inventoryData->syncedAt,
        ];
    }
}
