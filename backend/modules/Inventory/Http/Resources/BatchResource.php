<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Modules\Inventory\Models\Batch
 */
class BatchResource extends JsonResource
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
            'batch_number' => $this->batch_number,
            'lot_number' => $this->lot_number,
            'supplier_id' => $this->supplier_id,
            'manufacture_date' => $this->manufacture_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'received_quantity' => (float) $this->received_quantity,
            'available_quantity' => (float) $this->available_quantity,
            'unit_cost' => $this->unit_cost ? (float) $this->unit_cost : null,
            'notes' => $this->notes,
            'custom_attributes' => $this->custom_attributes,
            'is_expired' => $this->isExpired(),
            'is_near_expiry' => $this->isNearExpiry(),
            'remaining_shelf_life_days' => $this->getRemainingShelfLife(),
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
