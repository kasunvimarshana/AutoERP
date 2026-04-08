<?php
declare(strict_types=1);
namespace Modules\Product\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'category_id' => $this->resource->categoryId,
            'name' => $this->resource->name,
            'sku' => $this->resource->sku,
            'barcode' => $this->resource->barcode,
            'type' => $this->resource->type,
            'status' => $this->resource->status,
            'description' => $this->resource->description,
            'unit' => $this->resource->unit,
            'cost_price' => $this->resource->costPrice,
            'sale_price' => $this->resource->salePrice,
            'is_trackable' => $this->resource->isTrackable,
            'has_variants' => $this->resource->hasVariants,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
