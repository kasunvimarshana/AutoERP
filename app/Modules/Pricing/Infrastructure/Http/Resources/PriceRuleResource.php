<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceRuleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->resource->id,
            'tenant_id'        => $this->resource->tenantId,
            'price_list_id'    => $this->resource->priceListId,
            'product_id'       => $this->resource->productId,
            'category_id'      => $this->resource->categoryId,
            'variant_id'       => $this->resource->variantId,
            'min_qty'          => $this->resource->minQty,
            'price'            => $this->resource->price,
            'discount_percent' => $this->resource->discountPercent,
            'start_date'       => $this->resource->startDate,
            'end_date'         => $this->resource->endDate,
            'created_at'       => $this->resource->createdAt,
            'updated_at'       => $this->resource->updatedAt,
        ];
    }
}
