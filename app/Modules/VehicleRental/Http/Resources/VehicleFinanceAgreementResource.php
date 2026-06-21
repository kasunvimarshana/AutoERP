<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class VehicleFinanceAgreementResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'agreement_number' => $this->agreement_number,
            'supplier' => $this->whenLoaded('supplier', fn () => $this->summary($this->supplier, ['supplier_number', 'name', 'display_name'])),
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->summary($this->vehicle, ['vehicle_number', 'registration_number', 'status'])),
            'agreement_date' => $this->agreement_date?->toDateString(),
            'starts_at' => $this->starts_at?->toISOString(),
            'matures_at' => $this->matures_at?->toISOString(),
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'symbol'])),
            'principal_amount' => $this->decimal($this->principal_amount),
            'initial_deposit_amount' => $this->decimal($this->initial_deposit_amount),
            'residual_value' => $this->decimal($this->residual_value),
            'interest_method' => $this->interest_method,
            'annual_interest_rate' => $this->decimal($this->annual_interest_rate),
            'installment_frequency' => $this->installment_frequency,
            'installment_count' => (int) $this->installment_count,
            'status' => $this->enumValue($this->status),
            'installments' => $this->loadedCollection('installments', fn ($installment): array => [
                'id' => (int) $installment->getKey(), 'installment_number' => (int) $installment->installment_number,
                'due_date' => $installment->due_date?->toDateString(), 'principal_due' => $this->decimal($installment->principal_due),
                'interest_due' => $this->decimal($installment->interest_due), 'fee_due' => $this->decimal($installment->fee_due),
                'tax_due' => $this->decimal($installment->tax_due), 'total_due' => $this->decimal($installment->total_due),
                'paid_amount' => $this->decimal($installment->paid_amount), 'balance_due' => $this->decimal($installment->balance_due),
                'status' => $this->enumValue($installment->status), 'invoice_id' => $installment->invoice_id,
            ]),
            'remarks' => $this->remarks,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
