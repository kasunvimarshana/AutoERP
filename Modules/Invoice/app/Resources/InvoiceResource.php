<?php

declare(strict_types=1);

namespace Modules\Invoice\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Invoice Resource
 *
 * Transforms Invoice model for API responses
 */
class InvoiceResource extends JsonResource
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
            'job_card_id' => $this->job_card_id,
            'customer_id' => $this->customer_id,
            'vehicle_id' => $this->vehicle_id,
            'branch_id' => $this->branch_id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'subtotal' => (float) $this->subtotal,
            'tax_rate' => (float) $this->tax_rate,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'balance' => (float) $this->balance,
            'status' => $this->status,
            'payment_terms' => $this->payment_terms,
            'notes' => $this->notes,
            'is_overdue' => $this->isOverdue(),
            'is_paid' => $this->isPaid(),
            'customer' => $this->whenLoaded('customer'),
            'vehicle' => $this->whenLoaded('vehicle'),
            'branch' => $this->whenLoaded('branch'),
            'job_card' => $this->whenLoaded('jobCard'),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'commissions' => DriverCommissionResource::collection($this->whenLoaded('commissions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
