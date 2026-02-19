<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Helpers\MathHelper;
use Modules\Product\Http\Resources\ProductResource;

class StockCountItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variance = $this->counted_quantity !== null && $this->system_quantity !== null
            ? MathHelper::subtract($this->counted_quantity, $this->system_quantity)
            : null;

        $variancePercentage = null;
        if ($variance !== null && ! MathHelper::equals($this->system_quantity, '0')) {
            $variancePercentage = MathHelper::multiply(
                MathHelper::divide($variance, $this->system_quantity, 6),
                '100',
                2
            );
        }

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'stock_count_id' => $this->stock_count_id,
            'product_id' => $this->product_id,
            'location_id' => $this->location_id,
            'system_quantity' => $this->system_quantity,
            'counted_quantity' => $this->counted_quantity,
            'variance' => $variance,
            'variance_percentage' => $variancePercentage,
            'has_variance' => $variance !== null && ! MathHelper::equals($variance, '0'),
            'notes' => $this->notes,
            'product' => new ProductResource($this->whenLoaded('product')),
            'location' => new StockLocationResource($this->whenLoaded('location')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
