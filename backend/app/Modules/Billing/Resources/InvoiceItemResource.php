<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'invoice_id' => $this->invoice_id,
            'sku_id' => $this->sku_id,
            'description' => $this->description,
            'quantity' => number_format((float) $this->quantity, 2, '.', ''),
            'unit_price' => number_format((float) $this->unit_price, 2, '.', ''),
            'discount' => number_format((float) $this->discount, 2, '.', ''),
            'tax_rate' => number_format((float) $this->tax_rate, 2, '.', ''),
            'line_total' => number_format((float) $this->line_total, 2, '.', ''),
            'invoice' => $this->whenLoaded('invoice', function () {
                return [
                    'id' => $this->invoice->id,
                    'invoice_number' => $this->invoice->invoice_number,
                ];
            }),
            'sku' => $this->whenLoaded('sku', function () {
                return $this->sku ? [
                    'id' => $this->sku->id,
                    'name' => $this->sku->name,
                    'sku_code' => $this->sku->sku_code,
                ] : null;
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
