<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalUsageLogResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'usage_number' => $this->usage_number,
            'allocation' => $this->whenLoaded(
                'allocation',
                fn () => $this->summary($this->allocation, ['allocation_number', 'status']),
            ),
            'vehicle' => $this->whenLoaded(
                'vehicle',
                fn () => $this->summary($this->vehicle, ['vehicle_number', 'registration_number']),
            ),
            'driver_assignment' => $this->whenLoaded('driverAssignment', function (): ?array {
                if ($this->driverAssignment === null) {
                    return null;
                }

                return [
                    'id' => (int) $this->driverAssignment->getKey(),
                    'employee' => $this->driverAssignment->relationLoaded('employee')
                        ? $this->summary($this->driverAssignment->employee, ['employee_number', 'display_name', 'first_name', 'last_name'])
                        : null,
                    'assignment_role' => $this->driverAssignment->assignment_role,
                    'assigned_from' => $this->driverAssignment->assigned_from?->toISOString(),
                    'assigned_to' => $this->driverAssignment->assigned_to?->toISOString(),
                    'is_primary' => (bool) $this->driverAssignment->is_primary,
                    'status' => $this->enumValue($this->driverAssignment->status),
                ];
            }),
            'driver' => $this->whenLoaded(
                'driver',
                fn () => $this->summary($this->driver, ['employee_number', 'first_name', 'last_name', 'display_name']),
            ),
            'usage_date' => $this->usage_date?->toDateString(),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'start_odometer' => $this->decimal($this->start_odometer),
            'end_odometer' => $this->decimal($this->end_odometer),
            'distance_km' => $this->decimal($this->distance_km),
            'net_operational_distance_km' => $this->decimal($this->net_operational_distance_km),
            'garage_distance_km' => $this->decimal($this->garage_distance_km),
            'internal_distance_km' => $this->decimal($this->internal_distance_km),
            'working_minutes' => (int) $this->working_minutes,
            'normal_overtime_minutes' => (int) $this->normal_overtime_minutes,
            'double_overtime_minutes' => (int) $this->double_overtime_minutes,
            'triple_overtime_minutes' => (int) $this->triple_overtime_minutes,
            'night_out_count' => $this->decimal($this->night_out_count),
            'trip_from' => $this->trip_from,
            'trip_to' => $this->trip_to,
            'trip_purpose' => $this->trip_purpose,
            'odometer_variance_reason' => $this->odometer_variance_reason,
            'status' => $this->enumValue($this->status),
            'events' => $this->loadedCollection('events', fn ($event): array => [
                'id' => (int) $event->getKey(),
                'sequence' => (int) $event->sequence,
                'event_type' => $this->enumValue($event->event_type),
                'applicability' => $this->enumValue($event->applicability),
                'occurred_at' => $event->occurred_at?->toISOString(),
                'quantity' => $this->decimal($event->quantity),
                'unit' => $event->unit,
                'reference_number' => $event->reference_number,
                'remarks' => $event->remarks,
            ]),
            'contexts' => $this->loadedCollection('contexts', function ($context) use ($request): array {
                $agreement = $context->relationLoaded('agreement') ? $context->agreement : null;

                return [
                    'id' => (int) $context->getKey(),
                    'financial_side' => $this->enumValue($context->financial_side),
                    'agreement' => $agreement === null
                        ? null
                        : $this->summary($agreement, ['agreement_number', 'agreement_kind']),
                    'rate_version_id' => (int) $context->rate_version_id,
                    'customer' => $agreement?->relationLoaded('customer')
                        ? $this->summary($agreement->customer, ['customer_number', 'name', 'display_name'])
                        : null,
                    'supplier' => $agreement?->relationLoaded('supplier')
                        ? $this->summary($agreement->supplier, ['supplier_number', 'name', 'display_name'])
                        : null,
                    'usage_fact' => $context->relationLoaded('usageFact') && $context->usageFact !== null
                        ? (new RentalUsageFactResource($context->usageFact->setRelation('context', $context)))->resolve($request)
                        : null,
                ];
            }),
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'reversed_at' => $this->reversed_at?->toISOString(),
            'reversal_reason' => $this->reversal_reason,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
