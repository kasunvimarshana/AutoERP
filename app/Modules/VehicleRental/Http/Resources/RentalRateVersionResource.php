<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalRateVersionResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'version_number' => (int) $this->version_number,
            'effective_from' => $this->effective_from?->toISOString(),
            'effective_to' => $this->effective_to?->toISOString(),
            'driver_mode' => $this->enumValue($this->driver_mode),
            'billing_cycle' => $this->enumValue($this->billing_cycle),
            'billing_basis' => $this->enumValue($this->billing_basis),
            'proration_rule' => $this->enumValue($this->proration_rule),
            'excess_km_method' => $this->enumValue($this->excess_km_method),
            'included_km' => $this->decimal($this->included_km),
            'included_hours' => $this->decimal($this->included_hours),
            'weekday_included_minutes' => (int) $this->weekday_included_minutes,
            'saturday_included_minutes' => (int) $this->saturday_included_minutes,
            'holiday_included_minutes' => (int) $this->holiday_included_minutes,
            'currency' => $this->whenLoaded('currency', fn () => $this->summary($this->currency, ['code', 'name', 'symbol'])),
            'tax_group' => $this->whenLoaded('taxGroup', fn () => $this->summary($this->taxGroup, ['code', 'name'])),
            'withholding_tax_group' => $this->whenLoaded('withholdingTaxGroup', fn () => $this->summary($this->withholdingTaxGroup, ['code', 'name'])),
            'status' => $this->enumValue($this->status),
            'components' => $this->loadedCollection('components', fn ($component): array => [
                'id' => (int) $component->getKey(),
                'vehicle_category' => $component->relationLoaded('vehicleCategory') ? $this->summary($component->vehicleCategory, ['code', 'name']) : null,
                'component_code' => $this->enumValue($component->component_code),
                'unit' => $this->enumValue($component->unit),
                'included_quantity' => $this->decimal($component->included_quantity),
                'rate' => $this->decimal($component->rate),
                'multiplier' => $this->decimal($component->multiplier),
                'minimum_amount' => $component->minimum_amount === null ? null : $this->decimal($component->minimum_amount),
                'maximum_amount' => $component->maximum_amount === null ? null : $this->decimal($component->maximum_amount),
                'is_taxable' => (bool) $component->is_taxable,
                'calculation_order' => (int) $component->calculation_order,
            ]),
            'approved_at' => $this->approved_at?->toISOString(),
        ];
    }
}
