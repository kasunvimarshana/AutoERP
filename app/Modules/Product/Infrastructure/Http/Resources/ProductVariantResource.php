<?php
declare(strict_types=1);
namespace Modules\Product\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class ProductVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'product_id' => $this->resource->productId,
            'name' => $this->resource->name,
            'sku' => $this->resource->sku,
            'attributes' => $this->resource->attributes,
            'cost_price' => $this->resource->costPrice,
            'sale_price' => $this->resource->salePrice,
            'stock_quantity' => $this->resource->stockQuantity,
            'is_active' => $this->resource->isActive,
        ];
    }
}
