<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Category\CategoryResource;
use App\Http\Resources\Stock\StockLevelResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'sku'              => $this->sku,
            'name'             => $this->name,
            'description'      => $this->description,
            'category_id'      => $this->category_id,
            'category'         => new CategoryResource($this->whenLoaded('category')),
            'unit_price'       => [
                'amount'   => (float) $this->unit_price,
                'currency' => config('inventory.default_currency', 'USD'),
                'formatted'=> number_format((float) $this->unit_price, 2),
            ],
            'cost_price'       => $this->cost_price !== null ? [
                'amount'   => (float) $this->cost_price,
                'currency' => config('inventory.default_currency', 'USD'),
                'formatted'=> number_format((float) $this->cost_price, 2),
            ] : null,
            'unit_of_measure'  => $this->unit_of_measure,
            'weight'           => $this->weight,
            'dimensions'       => $this->dimensions,
            'barcode'          => $this->barcode,
            'images'           => $this->images ?? [],
            'attributes'       => $this->attributes ?? [],
            'minimum_stock'    => (int) $this->minimum_stock,
            'reorder_point'    => (int) $this->reorder_point,
            'reorder_quantity' => (int) ($this->reorder_quantity ?? 0),
            'is_active'        => (bool) $this->is_active,
            'stock_summary'    => $this->whenLoaded('stockLevels', function () {
                $levels = $this->stockLevels;
                return [
                    'total_available' => $levels->sum('quantity_available'),
                    'total_reserved'  => $levels->sum('quantity_reserved'),
                    'total_on_hand'   => $levels->sum('quantity_on_hand'),
                    'warehouse_count' => $levels->count(),
                    'is_low_stock'    => $levels->sum('quantity_available') <= $this->reorder_point,
                ];
            }),
            'stock_levels'     => StockLevelResource::collection($this->whenLoaded('stockLevels')),
            'created_at'       => $this->created_at?->toISOString(),
            'updated_at'       => $this->updated_at?->toISOString(),
            'deleted_at'       => $this->deleted_at?->toISOString(),
        ];
    }
}
