<?php
namespace Modules\Accounting\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'reference_number' => $this->reference_number,
            'status'           => $this->status,
            'method'           => $this->method,
            'amount'           => $this->amount,
            'currency'         => $this->currency,
            'payable_type'     => $this->payable_type,
            'payable_id'       => $this->payable_id,
            'paid_by'          => $this->paid_by,
            'paid_at'          => $this->paid_at?->toIso8601String(),
            'notes'            => $this->notes,
            'journal_entry_id' => $this->journal_entry_id,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
