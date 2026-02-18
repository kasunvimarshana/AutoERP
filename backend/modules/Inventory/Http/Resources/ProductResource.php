<?php

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_identifier' => $this->sku,
            'product_name' => $this->name,
            'product_description' => $this->description,
            'product_classification' => [
                'type' => $this->product_type?->value,
                'category_id' => $this->category_id,
                'category_name' => $this->category?->name,
            ],
            'identification' => [
                'barcode' => $this->barcode,
                'manufacturer_name' => $this->manufacturer,
                'brand_name' => $this->brand,
            ],
            'inventory_tracking' => [
                'tracks_inventory' => $this->track_inventory,
                'tracks_batches' => $this->track_batches,
                'tracks_serials' => $this->track_serials,
                'expires' => $this->has_expiry,
            ],
            'reorder_parameters' => $this->buildReorderInfo(),
            'costing_details' => $this->buildCostingInfo(),
            'pricing' => [
                'selling_price' => $this->formatCurrency($this->selling_price),
                'raw_price' => $this->selling_price,
            ],
            'physical_specifications' => $this->buildPhysicalSpecs(),
            'product_status' => $this->status?->value,
            'availability' => $this->status?->value === 'active',
            'custom_fields' => $this->custom_attributes ?? [],
            'visual_media' => [
                'primary_image' => $this->image_url,
            ],
            'measurement_unit' => $this->when(
                $this->relationLoaded('baseUom'),
                fn() => [
                    'uom_id' => $this->base_uom_id,
                    'uom_name' => $this->baseUom?->name,
                ]
            ),
            'product_variants' => $this->when(
                $this->relationLoaded('variants'),
                fn() => ProductVariantResource::collection($this->variants)
            ),
            'record_metadata' => [
                'created_timestamp' => $this->created_at?->toIso8601String(),
                'modified_timestamp' => $this->updated_at?->toIso8601String(),
                'archived_timestamp' => $this->deleted_at?->toIso8601String(),
            ],
        ];
    }

    private function buildReorderInfo(): array
    {
        return [
            'minimum_level' => $this->reorder_level,
            'reorder_quantity' => $this->reorder_quantity,
            'auto_reorder_enabled' => !is_null($this->reorder_level) && !is_null($this->reorder_quantity),
        ];
    }

    private function buildCostingInfo(): array
    {
        return [
            'costing_method' => $this->cost_method?->value,
            'standard_cost' => $this->formatCurrency($this->standard_cost),
            'average_cost' => $this->formatCurrency($this->average_cost),
            'last_purchase_cost' => $this->formatCurrency($this->last_purchase_cost),
        ];
    }

    private function buildPhysicalSpecs(): array
    {
        $specs = [];

        if ($this->weight) {
            $specs['weight'] = [
                'value' => $this->weight,
                'unit' => $this->weight_uom ?? 'kg',
                'display' => "{$this->weight} {$this->weight_uom}",
            ];
        }

        if ($this->length && $this->width && $this->height) {
            $specs['dimensions'] = [
                'length' => $this->length,
                'width' => $this->width,
                'height' => $this->height,
                'unit' => $this->dimension_uom ?? 'cm',
                'display' => "{$this->length}x{$this->width}x{$this->height} {$this->dimension_uom}",
            ];
        }

        return $specs;
    }

    private function formatCurrency($amount): ?string
    {
        if (is_null($amount)) {
            return null;
        }

        return number_format((float) $amount, 2, '.', ',');
    }
}
