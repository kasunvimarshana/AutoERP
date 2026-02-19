<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'goods_receipt_id' => $this->goods_receipt_id,
            'purchase_order_item_id' => $this->purchase_order_item_id,
            'product_id' => $this->product_id,
            'unit_id' => $this->unit_id,
            'quantity_received' => $this->quantity_received,
            'quantity_accepted' => $this->quantity_accepted,
            'quantity_rejected' => $this->quantity_rejected,
            'notes' => $this->notes,
            'product' => $this->when(
                $this->relationLoaded('product'),
                fn () => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'code' => $this->product->code,
                    'sku' => $this->product->sku,
                ]
            ),
            'unit' => $this->when(
                $this->relationLoaded('unit'),
                fn () => [
                    'id' => $this->unit->id,
                    'name' => $this->unit->name,
                    'symbol' => $this->unit->symbol,
                ]
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
