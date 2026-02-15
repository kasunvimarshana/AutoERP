<?php

declare(strict_types=1);

namespace App\Http\Resources\Invoice;

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
            'sales_order_id' => $this->sales_order_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'customer_tax_number' => $this->customer_tax_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'invoice_date' => $this->invoice_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'payment_date' => $this->payment_date?->toDateString(),
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency?->code,
            'exchange_rate' => $this->exchange_rate,
            'subtotal' => $this->subtotal,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'adjustment_amount' => $this->adjustment_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'balance_amount' => $this->balance_amount,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'notes' => $this->notes,
            'terms_and_conditions' => $this->terms_and_conditions,
            'is_editable' => $this->isEditable(),
            'is_paid' => $this->isPaid(),
            'is_overdue' => $this->isOverdue(),
            'is_partially_paid' => $this->isPartiallyPaid(),
            'days_overdue' => $this->getDaysOverdue(),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => InvoicePaymentResource::collection($this->whenLoaded('payments')),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
