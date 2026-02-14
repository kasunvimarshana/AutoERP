<?php

namespace App\Modules\Billing\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'invoice_id' => $this->invoice_id,
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date->toIso8601String(),
            'amount' => number_format((float) $this->amount, 2, '.', ''),
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'status' => $this->status,
            'invoice' => $this->whenLoaded('invoice', function () {
                return [
                    'id' => $this->invoice->id,
                    'invoice_number' => $this->invoice->invoice_number,
                    'total_amount' => number_format((float) $this->invoice->total_amount, 2, '.', ''),
                    'balance' => number_format((float) $this->invoice->balance, 2, '.', ''),
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
