<?php

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'allocated_amount' => number_format((float) $this->amount, 2, '.', ','),
            'allocation_date' => $this->created_at?->toDateString(),
        ];
    }
}
