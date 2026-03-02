<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Ecommerce\Domain\Entities\StorefrontProduct;

class StorefrontProductResource extends JsonResource
{
    /** @var StorefrontProduct */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'product_id' => $this->resource->productId,
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'price' => $this->resource->price,
            'currency' => $this->resource->currency,
            'is_active' => $this->resource->isActive,
            'is_featured' => $this->resource->isFeatured,
            'sort_order' => $this->resource->sortOrder,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
