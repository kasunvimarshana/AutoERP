<?php

declare(strict_types=1);

namespace App\Modules\POS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POSTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'session_id' => $this->session_id,
            'transaction_number' => $this->transaction_number,
            'transaction_date' => $this->transaction_date?->toIso8601String(),
            'customer_id' => $this->customer_id,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'change_amount' => $this->change_amount,
            'payment_method_id' => $this->payment_method_id,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => POSTransactionItemResource::collection($this->whenLoaded('items')),
            'customer' => $this->when($this->relationLoaded('customer'), $this->customer),
        ];
    }
}
