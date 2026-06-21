<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class InvoiceCreditAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'credit_source_type' => $this->credit_source_type,
            'credit_source_id' => (int) $this->credit_source_id,
            'invoice_total' => (string) $this->invoice_total,
            'previously_allocated_amount' => (string) $this->previously_allocated_amount,
            'allocated_amount' => (string) $this->allocated_amount,
            'remaining_invoice_balance' => (string) $this->remaining_invoice_balance,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
