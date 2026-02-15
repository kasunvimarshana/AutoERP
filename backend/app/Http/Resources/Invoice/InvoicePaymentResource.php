<?php

declare(strict_types=1);

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoicePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'amount' => $this->amount,
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency?->code,
            'exchange_rate' => $this->exchange_rate,
            'amount_in_invoice_currency' => $this->amount_in_invoice_currency,
            'reference_number' => $this->reference_number,
            'transaction_id' => $this->transaction_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
