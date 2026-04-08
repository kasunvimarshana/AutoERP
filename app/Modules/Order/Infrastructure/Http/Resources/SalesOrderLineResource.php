<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class SalesOrderLineResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'product_id'        => $this->product_id,
            'variant_id'        => $this->variant_id,
            'sku'               => $this->sku,
            'product_name'      => $this->product_name,
            'unit_of_measure'   => $this->unit_of_measure,
            'quantity_ordered'  => $this->quantity_ordered,
            'quantity_shipped'  => $this->quantity_shipped,
            'quantity_returned' => $this->quantity_returned,
            'unit_price'        => $this->unit_price,
            'discount_percent'  => $this->discount_percent,
            'discount_amount'   => $this->discount_amount,
            'tax_rate'          => $this->tax_rate,
            'tax_amount'        => $this->tax_amount,
            'line_total'        => $this->line_total,
        ];
    }
}
