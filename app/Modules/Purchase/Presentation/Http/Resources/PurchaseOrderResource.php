<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'po_number' => $this->resource->po_number,
            'reference' => $this->resource->reference ?? null,
            'supplier_id' => $this->resource->supplier_id,
            'supplier_name' => $this->resource->supplier_name ?? null,
            'warehouse_id' => $this->resource->warehouse_id,
            'warehouse_name' => $this->resource->warehouse_name ?? null,
            'order_date' => $this->resource->order_date,
            'expected_date' => $this->resource->expected_date,
            'status' => $this->resource->status,
            'invoice_status' => $this->resource->invoice_status,
            'subtotal' => $this->money($this->resource->subtotal ?? 0),
            'discount_total' => $this->money($this->resource->discount_total ?? 0),
            'tax_total' => $this->money($this->resource->tax_total ?? 0),
            'grand_total' => $this->money($this->resource->grand_total ?? 0),
            'paid_amount' => $this->money($this->resource->paid_amount ?? 0),
            'balance' => $this->money($this->resource->balance ?? 0),
            'supplier_balance' => $this->resource->supplier_balance ?? null,
            'notes' => $this->resource->notes ?? null,
            'lines' => $this->when(isset($this->resource->lines), $this->resource->lines ?? []),
            'grns' => $this->when(isset($this->resource->grns), $this->resource->grns ?? []),
            'created_at' => $this->resource->created_at,
        ];
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
