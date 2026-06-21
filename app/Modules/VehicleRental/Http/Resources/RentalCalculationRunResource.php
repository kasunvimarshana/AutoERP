<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalCalculationRunResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'billing_period' => $this->whenLoaded('billingPeriod', fn () => [
                'id' => (int) $this->billingPeriod->getKey(),
                'agreement' => $this->billingPeriod->relationLoaded('agreement') ? $this->summary($this->billingPeriod->agreement, ['agreement_number', 'agreement_kind']) : null,
                'financial_side' => $this->enumValue($this->billingPeriod->financial_side),
                'period_start' => $this->billingPeriod->period_start?->toISOString(),
                'period_end' => $this->billingPeriod->period_end?->toISOString(),
                'status' => $this->enumValue($this->billingPeriod->status),
            ]),
            'run_version' => (int) $this->run_version,
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'symbol'])),
            'calculation_status' => $this->enumValue($this->calculation_status),
            'document_status' => $this->enumValue($this->document_status),
            'net_total' => $this->decimal($this->net_total),
            'discount_total' => $this->decimal($this->discount_total),
            'tax_total' => $this->decimal($this->tax_total),
            'withholding_total' => $this->decimal($this->withholding_total),
            'grand_total' => $this->decimal($this->grand_total),
            'lines' => $this->loadedCollection('lines', fn ($line): array => [
                'id' => (int) $line->getKey(), 'line_number' => (int) $line->line_number,
                'source_type' => $line->source_type, 'source_id' => (int) $line->source_id,
                'component_code' => $this->enumValue($line->component_code), 'description' => $line->description,
                'measured_quantity' => $this->decimal($line->measured_quantity), 'allowed_quantity' => $this->decimal($line->allowed_quantity),
                'chargeable_quantity' => $this->decimal($line->chargeable_quantity), 'unit' => $line->unit,
                'rate' => $this->decimal($line->rate), 'multiplier' => $this->decimal($line->multiplier),
                'net_amount' => $this->decimal($line->net_amount), 'discount_amount' => $this->decimal($line->discount_amount),
                'tax_amount' => $this->decimal($line->tax_amount), 'withholding_amount' => $this->decimal($line->withholding_amount),
                'total_amount' => $this->decimal($line->total_amount), 'applied_rule' => $line->applied_rule,
                'status' => $this->enumValue($line->status),
            ]),
            'calculated_at' => $this->calculated_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
