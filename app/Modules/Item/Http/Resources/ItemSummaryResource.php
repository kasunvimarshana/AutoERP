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
            'standard_price' => $this->standard_price === null ? null : (string) $this->standard_price,
            'standard_price_basis' => 'per_base_uom_tax_exclusive_tenant_base_currency',
            'default_tax_group' => $this->whenLoaded('defaultTaxGroup', fn () => $this->namedResource($this->defaultTaxGroup)),
            'purchase_tax_group' => $this->whenLoaded('purchaseTaxGroup', fn () => $this->namedResource($this->purchaseTaxGroup)),
            'sales_tax_group' => $this->whenLoaded('salesTaxGroup', fn () => $this->namedResource($this->salesTaxGroup)),
            'is_stockable' => (bool) $this->is_stockable,
            'is_combo' => (bool) $this->is_combo,
            'is_tax_exempt' => (bool) ($this->is_tax_exempt ?? false),
            'default_tax_group_id' => $this->default_tax_group_id,
            'purchase_tax_group_id' => $this->purchase_tax_group_id,
            'sales_tax_group_id' => $this->sales_tax_group_id,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
