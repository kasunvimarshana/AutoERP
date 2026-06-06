<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Http\Resources\ItemSummaryResource;
use Modules\Supplier\Http\Resources\Concerns\FormatsSupplierResources;

final class SupplierItemMappingResource extends JsonResource
{
    use FormatsSupplierResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'item' => $this->relationLoaded('item') && $this->item
                ? (new ItemSummaryResource($this->item))->resolve($request)
                : null,
            'variant' => $this->relationLoaded('variant') ? $this->namedResource($this->variant) : null,
            'supplier_item_code' => $this->supplier_item_code,
            'supplier_item_name' => $this->supplier_item_name,
            'default_purchase_uom' => $this->relationLoaded('defaultPurchaseUom')
                ? $this->namedResource($this->defaultPurchaseUom, true)
                : null,
            'minimum_order_quantity' => (string) $this->minimum_order_quantity,
            'lead_time_days' => $this->lead_time_days,
            'is_preferred' => (bool) $this->is_preferred,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
