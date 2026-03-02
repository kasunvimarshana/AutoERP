<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Domain\Entities\ProductAttribute;

class ProductAttributeResource extends JsonResource
{
    /** @var ProductAttribute */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'product_id' => $this->resource->productId,
            'tenant_id' => $this->resource->tenantId,
            'attribute_key' => $this->resource->attributeKey,
            'attribute_label' => $this->resource->attributeLabel,
            'attribute_value' => $this->resource->attributeValue,
            'attribute_type' => $this->resource->attributeType,
            'sort_order' => $this->resource->sortOrder,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
