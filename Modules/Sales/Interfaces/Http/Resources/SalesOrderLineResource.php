<?php

declare(strict_types=1);

namespace Modules\Sales\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Domain\Entities\SalesOrderLine;

class SalesOrderLineResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var SalesOrderLine $line */
        $line = $this->resource;

        return [
            'id' => $line->id,
            'product_id' => $line->productId,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => $line->unitPrice,
            'tax_rate' => $line->taxRate,
            'discount_rate' => $line->discountRate,
            'line_total' => $line->lineTotal,
        ];
    }
}
