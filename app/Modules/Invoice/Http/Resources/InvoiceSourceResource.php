<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class InvoiceSourceResource extends ModuleResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'source_type' => $this->source_type,
            'source_id' => (int) $this->source_id,
            'source_document_number' => $this->source_document_number,
            'source_document_date' => $this->source_document_date?->toDateString(),
            'source_subtotal' => (string) $this->source_subtotal,
            'source_adjustment_total' => (string) $this->source_adjustment_total,
            'source_grand_total' => (string) $this->source_grand_total,
            'invoiced_amount' => (string) $this->invoiced_amount,
            'allocated_adjustment_amount' => (string) $this->allocated_adjustment_amount,
        ];
    }
}
