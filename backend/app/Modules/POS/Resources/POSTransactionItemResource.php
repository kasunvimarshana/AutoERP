<?php

namespace App\Modules\POS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class POSTransactionItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'pos_transaction_id' => $this->pos_transaction_id,
            'sku_id' => $this->sku_id,
            'quantity' => number_format((float) $this->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $this->unit_price, 2, '.', ''),
            'discount' => number_format((float) $this->discount, 2, '.', ''),
            'tax_rate' => number_format((float) $this->tax_rate, 2, '.', ''),
            'line_total' => number_format((float) $this->line_total, 2, '.', ''),
            'sku' => $this->whenLoaded('sku', function () {
                return [
                    'id' => $this->sku->id,
                    'sku_code' => $this->sku->sku_code,
                    'barcode' => $this->sku->barcode,
                    'product_id' => $this->sku->product_id,
                ];
            }),
            'pos_transaction' => $this->whenLoaded('posTransaction', function () {
                return [
                    'id' => $this->posTransaction->id,
                    'transaction_number' => $this->posTransaction->transaction_number,
                    'status' => $this->posTransaction->status,
                ];
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
