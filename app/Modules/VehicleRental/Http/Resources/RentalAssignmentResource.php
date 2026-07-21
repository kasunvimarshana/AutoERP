<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RentalAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'side' => $this->enum($this->side),
            'status' => $this->enum($this->status),
            'agreement' => $this->whenLoaded('agreement', fn () => [
                'id' => (int) $this->agreement->getKey(),
                'code' => $this->agreement->agreement_number,
                'name' => $this->agreement->agreement_number,
                'kind' => $this->enum($this->agreement->kind),
                'party' => $this->agreement->kind->value === 'customer'
                    ? $this->party($this->agreement->customer)
                    : $this->party($this->agreement->supplier),
            ]),
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicle === null ? null : [
                'id' => (int) $this->vehicle->getKey(),
                'code' => $this->vehicle->vehicle_number,
                'name' => trim(($this->vehicle->registration_number ?? $this->vehicle->vehicle_number).' '.($this->vehicle->model?->name ?? '')),
                'registration_number' => $this->vehicle->registration_number,
                'odometer_reading' => $this->vehicle->odometer_reading === null ? null : (string) $this->vehicle->odometer_reading,
                'odometer_unit' => $this->vehicle->odometer_unit,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => $this->driver === null ? null : [
                'id' => (int) $this->driver->getKey(),
                'code' => $this->driver->employee_number,
                'name' => $this->driver->display_name,
            ]),
            'source_assignment' => $this->whenLoaded('sourceAssignment', fn () => $this->sourceAssignment === null ? null : [
                'id' => (int) $this->sourceAssignment->getKey(),
                'agreement' => $this->sourceAssignment->relationLoaded('agreement') ? [
                    'id' => (int) $this->sourceAssignment->agreement->getKey(),
                    'code' => $this->sourceAssignment->agreement->agreement_number,
                    'name' => $this->sourceAssignment->agreement->supplier?->display_name ?? $this->sourceAssignment->agreement->agreement_number,
                ] : null,
            ]),
            'replaces_assignment' => $this->whenLoaded('replacesAssignment', fn () => $this->replacesAssignment === null ? null : [
                'id' => (int) $this->replacesAssignment->getKey(),
                'vehicle' => $this->replacesAssignment->relationLoaded('vehicle') && $this->replacesAssignment->vehicle !== null ? [
                    'id' => (int) $this->replacesAssignment->vehicle->getKey(),
                    'code' => $this->replacesAssignment->vehicle->vehicle_number,
                    'name' => trim(
                        ($this->replacesAssignment->vehicle->registration_number ?? $this->replacesAssignment->vehicle->vehicle_number)
                        .' '.($this->replacesAssignment->vehicle->model?->name ?? ''),
                    ),
                ] : null,
            ]),
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'handover_odometer' => $this->handover_odometer === null ? null : (string) $this->handover_odometer,
            'return_odometer' => $this->return_odometer === null ? null : (string) $this->return_odometer,
            'self_drive' => (bool) $this->self_drive,
            'replacement_reason' => $this->replacement_reason,
            'custody_events' => $this->whenLoaded('custodyEvents', fn () => $this->custodyEvents->map(fn ($event) => [
                'id' => (int) $event->getKey(),
                'event_type' => $this->enum($event->event_type),
                'event_at' => $event->event_at?->toISOString(),
                'odometer' => $event->odometer === null ? null : (string) $event->odometer,
                'fuel_level' => $event->fuel_level,
                'condition_notes' => $event->condition_notes,
                'damage_notes' => $event->damage_notes,
            ])->values()->all()),
        ];
    }

    private function enum(mixed $value): mixed { return $value instanceof \BackedEnum ? $value->value : $value; }
    private function party(mixed $party): ?array { return $party === null ? null : ['id' => (int) $party->getKey(), 'code' => $party->code ?? null, 'name' => $party->display_name ?? $party->name]; }
}
