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
            'base_uom' => $this->whenLoaded('baseUom', fn () => $this->namedResource($this->baseUom, true)),
            'is_stockable' => (bool) $this->is_stockable,
            'is_combo' => (bool) $this->is_combo,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
