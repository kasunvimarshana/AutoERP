<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PurchaseReturnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'return_number' => $this->resource->return_number,
            'reference' => $this->resource->reference ?? null,
            'original_grn_id' => $this->resource->original_grn_id,
            'grn_number' => $this->resource->grn_number ?? null,
            'original_invoice_id' => $this->resource->original_invoice_id,
            'supplier_id' => $this->resource->supplier_id,
            'supplier_name' => $this->resource->supplier_name ?? null,
            'return_date' => $this->resource->return_date,
            'return_reason' => $this->resource->return_reason,
            'status' => $this->resource->status,
            'subtotal' => $this->money($this->resource->subtotal ?? 0),
            'line_discount_total' => $this->money($this->resource->line_discount_total ?? 0),
            'line_tax_total' => $this->money($this->resource->line_tax_total ?? 0),
            'header_discount_type' => $this->resource->header_discount_type ?? null,
            'header_discount_value' => $this->resource->header_discount_value === null ? null : $this->money($this->resource->header_discount_value),
            'header_discount_amount' => $this->money($this->resource->header_discount_amount ?? 0),
            'header_tax_group_id' => $this->resource->header_tax_group_id ?? null,
            'header_tax_amount' => $this->money($this->resource->header_tax_amount ?? 0),
            'discount_total' => $this->money($this->resource->discount_total ?? 0),
            'tax_total' => $this->money($this->resource->tax_total ?? 0),
            'debit_note_total' => $this->money($this->resource->debit_note_total ?? 0),
            'credit_note_total' => $this->money($this->resource->credit_note_total ?? 0),
            'charge_total' => $this->money($this->resource->debit_note_total ?? 0),
            'restocking_total' => $this->money($this->resource->line_restocking_total ?? 0),
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
