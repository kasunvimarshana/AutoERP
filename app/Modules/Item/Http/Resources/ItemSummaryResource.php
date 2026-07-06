<?php

declare(strict_types=1);

namespace Modules\Item\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Item\Http\Resources\Concerns\FormatsItemResources;

final class ItemSummaryResource extends JsonResource
{
    use FormatsItemResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'code' => $this->code,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'item_type' => $this->enumValue($this->item_type),
            'tracking_type' => $this->enumValue($this->tracking_type),
            'costing_method' => $this->enumValue($this->costing_method),
            'category' => $this->whenLoaded('category', fn () => $this->namedResource($this->category)),
            'brand' => $this->whenLoaded('brand', fn () => $this->namedResource($this->brand)),
            'tenant_base_currency' => $this->whenLoaded('tenant', fn () => $this->namedResource($this->tenant?->baseCurrency, true)),
            'base_uom' => $this->whenLoaded('baseUom', fn () => $this->namedResource($this->baseUom, true)),
            'default_tax_group' => $this->whenLoaded('defaultTaxGroup', fn () => $this->namedResource($this->defaultTaxGroup)),
            'purchase_tax_group' => $this->whenLoaded('purchaseTaxGroup', fn () => $this->namedResource($this->purchaseTaxGroup)),
            'sales_tax_group' => $this->whenLoaded('salesTaxGroup', fn () => $this->namedResource($this->salesTaxGroup)),
            'is_stockable' => (bool) $this->is_stockable,
            'is_combo' => (bool) $this->is_combo,
            'is_tax_exempt' => (bool) ($this->is_tax_exempt ?? false),
            'default_tax_group_id' => $this->default_tax_group_id,
            'purchase_tax_group_id' => $this->purchase_tax_group_id,
            'sales_tax_group_id' => $this->sales_tax_group_id,
            'resolved_service_unit_price' => $this->when(
                array_key_exists('resolved_service_unit_price', $this->resource->getAttributes()),
                fn () => $this->resource->getAttribute('resolved_service_unit_price'),
            ),
            'resolved_purchase_unit_price' => $this->when(
                array_key_exists('resolved_purchase_unit_price', $this->resource->getAttributes()),
                fn () => $this->resource->getAttribute('resolved_purchase_unit_price'),
            ),
            'available_stock_quantity' => $this->when(
                array_key_exists('available_stock_quantity', $this->resource->getAttributes()),
                fn () => $this->resource->getAttribute('available_stock_quantity'),
            ),
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
