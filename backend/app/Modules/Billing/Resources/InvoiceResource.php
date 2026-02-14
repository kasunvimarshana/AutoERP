<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date->toIso8601String(),
            'due_date' => $this->due_date->toIso8601String(),
            'status' => $this->status,
            'subtotal' => number_format((float) $this->subtotal, 2, '.', ''),
            'tax_amount' => number_format((float) $this->tax_amount, 2, '.', ''),
            'discount_amount' => number_format((float) $this->discount_amount, 2, '.', ''),
            'total_amount' => number_format((float) $this->total_amount, 2, '.', ''),
            'paid_amount' => number_format((float) $this->paid_amount, 2, '.', ''),
            'balance' => number_format((float) $this->balance, 2, '.', ''),
            'notes' => $this->notes,
            'is_overdue' => $this->isOverdue(),
            'items' => $this->whenLoaded('items', function () {
                return InvoiceItemResource::collection($this->items);
            }),
            'payments' => $this->whenLoaded('payments', function () {
                return PaymentResource::collection($this->payments);
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                ] : null;
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
