<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalInspectionResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'agreement_vehicle_id' => (int) $this->agreement_vehicle_id,
            'vehicle_id' => (int) $this->vehicle_id,
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicleSummary($this->vehicle)),
            'inspected_at' => $this->inspected_at?->toISOString(),
            'odometer' => (string) $this->odometer,
            'fuel_level' => $this->fuel_level === null ? null : (string) $this->fuel_level,
            'condition_notes' => $this->condition_notes,
            'damage_notes' => $this->damage_notes,
            'damage_amount' => $this->damage_amount === null ? null : (string) $this->damage_amount,
            'is_damage_billable' => $this->is_damage_billable === null ? null : (bool) $this->is_damage_billable,
            'attachments' => $this->attachments ?? [],
            'inspected_by' => $this->inspected_by,
        ];
    }
}
