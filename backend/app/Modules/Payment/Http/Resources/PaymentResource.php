<?php

declare(strict_types=1);

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_type' => $this->payment_type,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'payment_method_id' => $this->payment_method_id,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'base_amount' => $this->base_amount,
            'reference_number' => $this->reference_number,
            'transaction_id' => $this->transaction_id,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'cheque_number' => $this->cheque_number,
            'cheque_date' => $this->cheque_date?->toDateString(),
            'card_last_four' => $this->card_last_four,
            'card_type' => $this->card_type,
            'notes' => $this->notes,
            'status' => $this->status,
            'reconciliation_status' => $this->reconciliation_status,
            'reconciled_at' => $this->reconciled_at?->toIso8601String(),
            'reconciled_by' => $this->reconciled_by,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Relationships
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
            'allocations' => PaymentAllocationResource::collection($this->whenLoaded('allocations')),
            'entity' => $this->when($this->relationLoaded('entity'), $this->entity),
            
            // Computed properties
            'amount_formatted' => number_format((float) $this->amount, 2),
            'base_amount_formatted' => number_format((float) $this->base_amount, 2),
            'is_fully_allocated' => $this->when(
                $this->relationLoaded('allocations'),
                fn () => $this->isFullyAllocated()
            ),
            'remaining_amount' => $this->when(
                $this->relationLoaded('allocations'),
                fn () => $this->getRemainingAmount()
            ),
        ];
    }
}
