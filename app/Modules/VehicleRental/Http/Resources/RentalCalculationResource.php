<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RentalCalculationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'calculation_number' => $this->calculation_number,
            'side' => $this->enum($this->side),
            'status' => $this->enum($this->status),
            'agreement' => $this->whenLoaded('agreement', fn () => [
                'id' => (int) $this->agreement->getKey(),
                'code' => $this->agreement->agreement_number,
                'name' => $this->agreement->agreement_number,
                'kind' => $this->enum($this->agreement->kind),
            ]),
            'rate_version' => $this->whenLoaded('rateVersion', fn () => [
                'id' => (int) $this->rateVersion->getKey(),
                'version_number' => (int) $this->rateVersion->version_number,
                'effective_from' => $this->rateVersion->effective_from?->toDateString(),
                'effective_to' => $this->rateVersion->effective_to?->toDateString(),
            ]),
            'currency' => $this->whenLoaded('currency', fn () => [
                'id' => (int) $this->currency->getKey(),
                'code' => $this->currency->code,
                'name' => $this->currency->name,
            ]),
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'chart_count' => (int) $this->chart_count,
            'operating_days' => (int) $this->operating_days,
            'commercial_km' => (string) $this->commercial_km,
            'included_km' => (string) $this->included_km,
            'excess_km' => (string) $this->excess_km,
            'subtotal_amount' => (string) $this->subtotal_amount,
            'lines' => $this->whenLoaded('lines', fn () => $this->lines->map(fn ($line) => [
                'id' => (int) $line->getKey(),
                'line_number' => (int) $line->line_number,
                'rate_code' => $this->enum($line->rate_code),
                'unit' => $this->enum($line->unit),
                'quantity' => (string) $line->quantity,
                'unit_rate' => (string) $line->unit_rate,
                'line_total' => (string) $line->line_total,
                'is_taxable' => (bool) $line->is_taxable,
                'description' => $line->description,
            ])->values()->all()),
            'sources' => $this->whenLoaded('sources', fn () => $this->sources->map(fn ($source) => [
                'id' => (int) $source->getKey(),
                'active' => $source->active_marker === true,
                'running_chart' => $source->relationLoaded('runningChart') ? [
                    'id' => (int) $source->runningChart->getKey(),
                    'chart_number' => $source->runningChart->chart_number,
                    'operational_date' => $source->runningChart->operational_date?->toDateString(),
                ] : null,
            ])->values()->all()),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
        ];
    }

    private function enum(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
