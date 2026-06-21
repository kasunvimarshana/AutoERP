<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Resources;

use Illuminate\Http\Request;

final class RentalAllocationResource extends RentalResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'allocation_number' => $this->allocation_number,
            'agreement' => $this->whenLoaded('agreement', fn () => $this->summary($this->agreement, ['agreement_number', 'agreement_kind', 'status'])),
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->summary($this->vehicle, ['vehicle_number', 'registration_number', 'status'])),
            'vehicle_source_type' => $this->enumValue($this->vehicle_source_type),
            'source_allocation' => $this->whenLoaded('sourceAllocation', fn () => $this->summary($this->sourceAllocation, ['allocation_number', 'status'])),
            'finance_agreement' => $this->whenLoaded('financeAgreement', fn () => $this->summary($this->financeAgreement, ['agreement_number', 'status'])),
            'replaces_allocation' => $this->whenLoaded('replacesAllocation', fn () => $this->summary($this->replacesAllocation, ['allocation_number', 'status'])),
            'allocated_from' => $this->allocated_from?->toISOString(),
            'allocated_to' => $this->allocated_to?->toISOString(),
            'actual_returned_at' => $this->actual_returned_at?->toISOString(),
            'start_odometer' => $this->start_odometer === null ? null : $this->decimal($this->start_odometer),
            'end_odometer' => $this->end_odometer === null ? null : $this->decimal($this->end_odometer),
            'status' => $this->enumValue($this->status),
            'remarks' => $this->remarks,
            'drivers' => $this->loadedCollection('driverAssignments', fn ($assignment): array => [
                'id' => (int) $assignment->getKey(),
                'employee' => $assignment->relationLoaded('employee') ? $this->summary($assignment->employee, ['employee_number', 'first_name', 'last_name', 'display_name']) : null,
                'assignment_role' => $assignment->assignment_role,
                'assigned_from' => $assignment->assigned_from?->toISOString(),
                'assigned_to' => $assignment->assigned_to?->toISOString(),
                'is_primary' => (bool) $assignment->is_primary,
                'status' => $this->enumValue($assignment->status),
            ]),
            'custody_events' => $this->whenLoaded('custodyEvents', fn () => RentalCustodyEventResource::collection($this->custodyEvents)->resolve($request), []),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
