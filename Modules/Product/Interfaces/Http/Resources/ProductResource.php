<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Domain\Entities\Product;

class ProductResource extends JsonResource
{
    /** @var Product */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'sku' => $this->resource->sku,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'type' => $this->resource->type,
            'uom' => $this->resource->uom,
            'buying_uom' => $this->resource->effectiveBuyingUom(),
            'selling_uom' => $this->resource->effectiveSellingUom(),
            'costing_method' => $this->resource->costingMethod,
            'cost_price' => $this->resource->costPrice,
            'sale_price' => $this->resource->salePrice,
            'barcode' => $this->resource->barcode,
            'status' => $this->resource->status,
            'created_at' => $this->resource->createdAt,
            'updated_at' => $this->resource->updatedAt,
        ];
    }
}
