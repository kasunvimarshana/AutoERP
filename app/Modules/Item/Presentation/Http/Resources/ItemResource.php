<?php

declare(strict_types=1);

namespace Modules\Item\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'tenant_id' => $this->resource->tenant_id,
            'organization_unit_id' => $this->resource->organization_unit_id,
            'item_code' => $this->resource->item_code,
            'name' => $this->resource->name,
            'display_name' => $this->resource->display_name,
            'item_type' => $this->resource->item_type,
            'base_uom' => $this->uom($this->resource->baseUom),
            'purchase_uom' => $this->uom($this->resource->purchaseUom),
            'sales_uom' => $this->uom($this->resource->salesUom),
            'sku' => $this->resource->sku,
            'barcode' => $this->resource->barcode,
            'description' => $this->resource->description,
            'track_inventory' => $this->resource->track_inventory,
            'is_stock_item' => $this->resource->is_stock_item,
            'is_service_item' => $this->resource->is_service_item,
            'cost_price' => $this->resource->cost_price,
            'sales_price' => $this->resource->sales_price,
            'reorder_level' => $this->resource->reorder_level,
            'reorder_quantity' => $this->resource->reorder_quantity,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'row_version' => $this->resource->row_version,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function uom(mixed $uom): ?array
    {
        if ($uom === null) {
            return null;
        }

        return [
            'id' => $uom->getKey(),
            'uom_code' => $uom->uom_code,
            'name' => $uom->name,
            'symbol' => $uom->symbol,
        ];
    }
}
