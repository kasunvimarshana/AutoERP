<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Domain\Entities\UomConversion;

class UomConversionResource extends JsonResource
{
    /** @var UomConversion */
    public $resource;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'product_id' => $this->resource->productId,
            'from_uom' => $this->resource->fromUom,
            'to_uom' => $this->resource->toUom,
            'factor' => $this->resource->factor,
            'created_at' => $this->resource->createdAt,
        ];
    }
}
