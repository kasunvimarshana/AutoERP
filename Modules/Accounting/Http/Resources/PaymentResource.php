<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'payment_number' => $this->payment_number,
            'customer_id' => $this->customer_id,
            'invoice_id' => $this->invoice_id,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'bank_account_id' => $this->bank_account_id,
            'status' => $this->status,
            'tags' => $this->tags,
            'customer' => $this->whenLoaded('customer'),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'bank_account' => new AccountResource($this->whenLoaded('bankAccount')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
