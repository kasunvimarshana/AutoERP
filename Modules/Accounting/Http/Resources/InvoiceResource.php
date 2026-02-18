<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'invoice_number' => $this->invoice_number,
            'customer_id' => $this->customer_id,
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'payment_terms' => $this->payment_terms,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'amount_due' => $this->amount_due,
            'status' => $this->status,
            'is_overdue' => $this->isOverdue(),
            'days_overdue' => $this->daysOverdue(),
            'notes' => $this->notes,
            'terms_conditions' => $this->terms_conditions,
            'reference' => $this->reference,
            'tags' => $this->tags,
            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'customer' => $this->whenLoaded('customer'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
