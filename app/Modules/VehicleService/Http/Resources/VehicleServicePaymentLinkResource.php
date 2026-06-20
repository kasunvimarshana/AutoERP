<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Http\Resources\ModuleResource;

final class VehicleServicePaymentLinkResource extends ModuleResource
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
            'payment_status' => $this->enum($this->payment?->status),
            'posting_status' => $this->enum($this->payment?->posting_status),
            'allocation_status' => $this->enum($this->payment?->allocation_status),
            'payment_method' => $paymentLine?->paymentMethod === null ? null : [
                'id' => (int) $paymentLine->paymentMethod->getKey(),
                'code' => $paymentLine->paymentMethod->code,
                'name' => $paymentLine->paymentMethod->name,
                'method_type' => $this->enum($paymentLine->paymentMethod->method_type),
            ],
            'reference_number' => $paymentLine?->reference_number ?? $this->payment?->reference_number,
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
