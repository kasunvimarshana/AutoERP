<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Inventory\Models\SerialNumber
 */
class SerialNumberResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'batch_id' => $this->batch_id,
            'serial_number' => $this->serial_number,
            'warehouse_id' => $this->warehouse_id,
            'location_id' => $this->location_id,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'description' => $this->status->description(),
            ],
            'customer_id' => $this->customer_id,
            'sale_order_id' => $this->sale_order_id,
            'sale_date' => $this->sale_date?->format('Y-m-d'),
            'warranty_start_date' => $this->warranty_start_date?->format('Y-m-d'),
            'warranty_end_date' => $this->warranty_end_date?->format('Y-m-d'),
            'purchase_cost' => $this->purchase_cost ? (float) $this->purchase_cost : null,
            'notes' => $this->notes,
            'custom_attributes' => $this->custom_attributes,
            'has_active_warranty' => $this->hasActiveWarranty(),
            'remaining_warranty_days' => $this->getRemainingWarrantyDays(),
            'is_available' => $this->isInStock(),
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'batch' => new BatchResource($this->whenLoaded('batch')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
