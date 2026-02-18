<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceLineItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'line_description' => $this->description,
            'product_reference' => $this->product_id,
            'quantity_ordered' => $this->quantity,
            'unit_price' => number_format((float) $this->unit_price, 2, '.', ','),
            'line_total' => number_format((float) $this->total, 2, '.', ','),
        ];
    }
}
