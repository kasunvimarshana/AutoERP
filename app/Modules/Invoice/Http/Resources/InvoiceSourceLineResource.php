<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceSourceLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'invoice_line_id' => $this->invoice_line_id,
            'source_type' => $this->source_type,
            'source_id' => (int) $this->source_id,
            'source_line_type' => $this->source_line_type,
            'source_line_id' => (int) $this->source_line_id,
            'source_quantity' => (string) $this->source_quantity,
            'previously_invoiced_quantity' => (string) $this->previously_invoiced_quantity,
            'invoiced_quantity' => (string) $this->invoiced_quantity,
            'remaining_quantity' => (string) $this->remaining_quantity,
            'source_unit_price' => (string) $this->source_unit_price,
            'source_line_total' => (string) $this->source_line_total,
            'invoiced_line_total' => (string) $this->invoiced_line_total,
        ];
    }
}
