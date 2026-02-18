<?php

namespace Modules\Purchasing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_reference' => $this->product_id,
            'product_information' => $this->when(
                $this->relationLoaded('product'),
                fn() => [
                    'product_name' => $this->product?->name,
                    'product_sku' => $this->product?->sku,
                ]
            ),
            'item_description' => $this->description,
            'quantity_ordered' => $this->quantity,
            'unit_cost' => number_format((float) $this->unit_price, 2, '.', ','),
            'line_subtotal' => number_format((float) $this->subtotal, 2, '.', ','),
            'line_tax' => number_format((float) $this->tax_amount, 2, '.', ','),
            'line_total' => number_format((float) $this->total, 2, '.', ','),
        ];
    }
}
