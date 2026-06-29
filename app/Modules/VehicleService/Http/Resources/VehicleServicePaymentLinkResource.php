<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleServicePaymentLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paymentLine = $this->payment?->relationLoaded('lines')
            ? $this->payment->lines->first()
            : null;

        return [
            'id' => (int) $this->getKey(),
            'payment_id' => (int) $this->payment_id,
            'payment_number' => $this->payment?->payment_number,
            'invoice_id' => $this->invoice_id,
            'invoice_number' => $this->invoice?->invoice_number,
            'allocated_amount' => (string) $this->allocated_amount,
            'status' => $this->status,
            'document_status' => $this->enum($this->payment?->document_status),
            'posting_status' => $this->enum($this->payment?->posting_status),
            'allocation_status' => $this->enum($this->payment?->allocation_status),
            'instrument_status' => $this->enum($this->payment?->instrument_status),
            'payment_method' => $paymentLine === null ? null : [
                'id' => $paymentLine->payment_method_id === null ? null : (int) $paymentLine->payment_method_id,
                'code' => $paymentLine->payment_method_code_snapshot,
                'name' => $paymentLine->payment_method_name_snapshot,
                'method_type' => $paymentLine->payment_method_type_snapshot,
                'requires_reference' => (bool) $paymentLine->requires_reference_snapshot,
                'requires_instrument_details' => (bool) $paymentLine->requires_instrument_details_snapshot,
            ],
            'reference_number' => $paymentLine?->reference_number ?? $this->payment?->reference_number,
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
