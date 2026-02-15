<?php

declare(strict_types=1);

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAllocationResource extends JsonResource
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
            'payment_id' => $this->payment_id,
            'allocatable_type' => $this->allocatable_type,
            'allocatable_id' => $this->allocatable_id,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'base_amount' => $this->base_amount,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Relationships
            'allocatable' => $this->when($this->relationLoaded('allocatable'), $this->allocatable),
            
            // Computed properties
            'amount_formatted' => number_format((float) $this->amount, 2),
            'base_amount_formatted' => number_format((float) $this->base_amount, 2),
        ];
    }
}
