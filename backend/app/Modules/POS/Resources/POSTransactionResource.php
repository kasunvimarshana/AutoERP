<?php

namespace App\Modules\POS\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class POSTransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'transaction_number' => $this->transaction_number,
            'customer_id' => $this->customer_id,
            'cashier_id' => $this->cashier_id,
            'transaction_date' => $this->transaction_date->toIso8601String(),
            'payment_method' => $this->payment_method,
            'subtotal' => number_format((float) $this->subtotal, 2, '.', ''),
            'tax_amount' => number_format((float) $this->tax_amount, 2, '.', ''),
            'discount_amount' => number_format((float) $this->discount_amount, 2, '.', ''),
            'total_amount' => number_format((float) $this->total_amount, 2, '.', ''),
            'amount_paid' => number_format((float) $this->amount_paid, 2, '.', ''),
            'change_amount' => number_format((float) $this->change_amount, 2, '.', ''),
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', function () {
                return POSTransactionItemResource::collection($this->items);
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                    'phone' => $this->customer->phone,
                ] : null;
            }),
            'cashier' => $this->whenLoaded('cashier', function () {
                return [
                    'id' => $this->cashier->id,
                    'name' => $this->cashier->name,
                    'email' => $this->cashier->email,
                ];
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
