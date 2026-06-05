<?php

declare(strict_types=1);

namespace Modules\Invoice\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'invoice_number' => $this->resource->invoice_number,
            'external_reference_number' => $this->resource->external_reference_number,
            'document_type' => $this->resource->document_type,
            'business_context' => $this->resource->business_context,
            'ledger_direction' => $this->resource->ledger_direction,
            'balance_effect' => $this->resource->balance_effect,
            'customer_id' => $this->resource->customer_id,
            'supplier_id' => $this->resource->supplier_id,
            'invoice_date' => $this->resource->invoice_date,
            'due_date' => $this->resource->due_date,
            'status' => $this->resource->status,
            'gross_total' => $this->money($this->resource->gross_total),
            'line_discount_total' => $this->money($this->resource->line_discount_total),
            'header_discount_total' => $this->money($this->resource->header_discount_total),
            'taxable_total' => $this->money($this->resource->taxable_total),
            'discount_total' => $this->money((float) $this->resource->line_discount_total + (float) $this->resource->header_discount_total),
            'tax_total' => $this->money($this->resource->tax_total),
            'charge_total' => $this->money($this->resource->charge_total),
            'rounding_adjustment' => $this->money($this->resource->rounding_adjustment),
            'debit_adjustment_total' => $this->money($this->resource->debit_adjustment_total),
            'credit_adjustment_total' => $this->money($this->resource->credit_adjustment_total),
            'refund_total' => $this->money($this->resource->refund_total),
            'write_off_total' => $this->money($this->resource->write_off_total),
            'adjustment_total' => number_format((float) $this->resource->debit_adjustment_total - (float) $this->resource->credit_adjustment_total, 4, '.', ''),
            'grand_total' => $this->money($this->resource->grand_total),
            'paid_total' => $this->money($this->resource->settled_total),
            'balance_due' => $this->money($this->resource->balance_total),
            'notes' => $this->resource->notes,
            'lines' => $this->when(isset($this->resource->lines), $this->resource->lines ?? []),
            'adjustments' => $this->when(isset($this->resource->adjustments), $this->resource->adjustments ?? []),
            'settlements' => $this->when(isset($this->resource->settlements), $this->resource->settlements ?? []),
            'created_at' => $this->resource->created_at,
        ];
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
