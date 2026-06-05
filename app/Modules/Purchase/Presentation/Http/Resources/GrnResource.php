<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GrnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'grn_number' => $this->resource->grn_number,
            'reference' => $this->resource->reference ?? null,
            'purchase_order_id' => $this->resource->purchase_order_id,
            'po_number' => $this->resource->po_number ?? null,
            'supplier_id' => $this->resource->supplier_id,
            'supplier_name' => $this->resource->supplier_name ?? null,
            'warehouse_id' => $this->resource->warehouse_id,
            'received_date' => $this->resource->received_date,
            'status' => $this->resource->status,
            'invoice_status' => $this->resource->invoice_status,
            'subtotal' => $this->money($this->resource->subtotal ?? 0),
            'discount_total' => $this->money($this->resource->discount_total ?? 0),
            'tax_total' => $this->money($this->resource->tax_total ?? 0),
            'grand_total' => $this->money($this->resource->grand_total ?? 0),
            'notes' => $this->resource->notes ?? null,
            'lines' => $this->when(isset($this->resource->lines), $this->resource->lines ?? []),
            'invoice_links' => $this->when(isset($this->resource->invoice_links), $this->resource->invoice_links ?? []),
            'created_at' => $this->resource->created_at,
        ];
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
