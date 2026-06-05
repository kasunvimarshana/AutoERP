<?php

declare(strict_types=1);

namespace Modules\Payment\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'payment_number' => $this->resource->payment_number,
            'party_type' => $this->resource->party_type,
            'party_id' => $this->resource->party_id,
            'payment_date' => $this->resource->payment_date,
            'amount' => $this->money($this->resource->amount),
            'allocated_amount' => $this->money($this->resource->allocated_amount),
            'unallocated_amount' => $this->money(max(0, (float) $this->resource->amount - (float) $this->resource->allocated_amount)),
            'direction' => $this->resource->direction,
            'payment_method_id' => $this->resource->payment_method_id,
            'status' => $this->resource->status,
            'reference' => $this->resource->reference ?? null,
            'notes' => $this->resource->notes ?? null,
            'journal_entry_id' => $this->resource->journal_entry_id ?? null,
            'allocations' => $this->when(isset($this->resource->allocations), $this->resource->allocations ?? []),
            'created_at' => $this->resource->created_at,
        ];
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 4, '.', '');
    }
}
