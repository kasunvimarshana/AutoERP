<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalChargeResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'billing_period_id' => $this->billing_period_id,
            'charge_run_id' => $this->charge_run_id,
            'charge_calculation_id' => $this->charge_calculation_id,
            'financial_side' => $this->enum($this->financial_side),
            'billing_period_start' => $this->billing_period_start?->toISOString(),
            'billing_period_end' => $this->billing_period_end?->toISOString(),
            'billing_cycle_key' => $this->billing_cycle_key,
            'period_sequence' => $this->period_sequence,
            'charge_type' => $this->charge_type,
            'description' => $this->description,
            'quantity' => (string) $this->quantity,
            'rate' => (string) $this->rate,
            'amount' => (string) $this->amount,
            'discount_amount' => (string) $this->discount_amount,
            'tax_amount' => (string) $this->tax_amount,
            'withholding_amount' => (string) $this->withholding_amount,
            'tax_group_id' => $this->tax_group_id,
            'total_amount' => (string) $this->total_amount,
            'invoice_status' => $this->getAttribute('computed_invoice_status') ?? $this->enum($this->invoice_status),
            'status' => $this->enum($this->status),
            'invoiced_quantity' => $this->getAttribute('invoiced_quantity'),
            'remaining_invoice_quantity' => $this->getAttribute('remaining_invoice_quantity'),
        ];
    }
}
