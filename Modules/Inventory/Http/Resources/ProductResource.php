<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'description' => $this->description,
            'product_type' => $this->product_type,
            'status' => $this->status,
            'list_price' => $this->list_price,
            'cost_price' => $this->cost_price,
            'currency' => $this->currency,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'dimension_unit' => $this->dimension_unit,
            'weight_unit' => $this->weight_unit,
            'reorder_level' => $this->reorder_level,
            'reorder_quantity' => $this->reorder_quantity,
            'lead_time_days' => $this->lead_time_days,
            'shelf_life_days' => $this->shelf_life_days,
            'is_serialized' => $this->is_serialized,
            'is_batch_tracked' => $this->is_batch_tracked,
            'image_url' => $this->image_url,
            'notes' => $this->notes,
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'unit_of_measure' => new UnitOfMeasureResource($this->whenLoaded('unitOfMeasure')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
