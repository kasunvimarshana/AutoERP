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
            'row_version' => (int) $this->row_version,
            'allocation_number' => $this->allocation_number,
            'agreement' => $this->whenLoaded('agreement', function () {
                $summary = $this->summary($this->agreement, ['agreement_number', 'agreement_kind', 'status']);
                if ($this->agreement->relationLoaded('supplier') && $this->agreement->supplier) {
                    $summary['supplier'] = ['id' => (int) $this->agreement->supplier->getKey(), 'name' => $this->agreement->supplier->name];
                }
                if ($this->agreement->relationLoaded('customer') && $this->agreement->customer) {
                    $summary['customer'] = ['id' => (int) $this->agreement->customer->getKey(), 'name' => $this->agreement->customer->name];
                }
                return $summary;
            }),
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->summary($this->vehicle, ['vehicle_number', 'registration_number', 'status'])),
            'vehicle_source_type' => $this->enumValue($this->vehicle_source_type),
            'source_allocation' => $this->whenLoaded('sourceAllocation', function () {
                $summary = $this->summary($this->sourceAllocation, ['allocation_number', 'status', 'vehicle_source_type']);
                if ($this->sourceAllocation->relationLoaded('vehicle') && $this->sourceAllocation->vehicle) {
                    $summary['vehicle'] = $this->summary($this->sourceAllocation->vehicle, ['vehicle_number', 'registration_number', 'status']);
                }
                if ($this->sourceAllocation->relationLoaded('agreement') && $this->sourceAllocation->agreement) {
                    $summary['agreement'] = [
                        'id' => (int) $this->sourceAllocation->agreement->getKey(),
                        'agreement_number' => $this->sourceAllocation->agreement->agreement_number,
                        'agreement_kind' => $this->enumValue($this->sourceAllocation->agreement->agreement_kind),
                        'supplier' => $this->sourceAllocation->agreement->relationLoaded('supplier') && $this->sourceAllocation->agreement->supplier ? [
                            'id' => (int) $this->sourceAllocation->agreement->supplier->getKey(),
                            'name' => $this->sourceAllocation->agreement->supplier->name,
                        ] : null,
                    ];
                }
                return $summary;
            }),
            'finance_agreement' => $this->whenLoaded('financeAgreement', fn () => $this->summary($this->financeAgreement, ['agreement_number', 'status'])),
            'replaces_allocation' => $this->whenLoaded('replacesAllocation', fn () => [
                'id' => (int) $this->replacesAllocation->getKey(),
                'allocation_number' => $this->replacesAllocation->allocation_number,
                'status' => $this->enumValue($this->replacesAllocation->status),
                'vehicle_source_type' => $this->enumValue($this->replacesAllocation->vehicle_source_type),
                'vehicle' => $this->replacesAllocation->relationLoaded('vehicle') ? $this->summary($this->replacesAllocation->vehicle, ['vehicle_number', 'registration_number', 'status']) : null,
            ]),
            'replacement_allocation' => $this->whenLoaded('replacementAllocation', fn () => [
                'id' => (int) $this->replacementAllocation->getKey(),
                'allocation_number' => $this->replacementAllocation->allocation_number,
                'status' => $this->enumValue($this->replacementAllocation->status),
                'vehicle_source_type' => $this->enumValue($this->replacementAllocation->vehicle_source_type),
                'vehicle' => $this->replacementAllocation->relationLoaded('vehicle') ? $this->summary($this->replacementAllocation->vehicle, ['vehicle_number', 'registration_number', 'status']) : null,
            ]),
            'replacement_record' => $this->whenLoaded('replacementRecord', fn () => [
                'id' => (int) $this->replacementRecord->getKey(),
                'replacement_number' => $this->replacementRecord->replacement_number,
                'replacement_at' => $this->replacementRecord->replacement_at?->toISOString(),
                'reason_code' => $this->replacementRecord->reason_code,
                'reason' => $this->replacementRecord->reason,
                'billing_continuity_rule' => $this->replacementRecord->billing_continuity_rule,
                'status' => $this->enumValue($this->replacementRecord->status),
            ]),
            'replacement_origin' => $this->whenLoaded('replacementAsNew', fn () => [
                'id' => (int) $this->replacementAsNew->getKey(),
                'replacement_number' => $this->replacementAsNew->replacement_number,
                'replacement_at' => $this->replacementAsNew->replacement_at?->toISOString(),
                'reason_code' => $this->replacementAsNew->reason_code,
                'reason' => $this->replacementAsNew->reason,
                'billing_continuity_rule' => $this->replacementAsNew->billing_continuity_rule,
                'status' => $this->enumValue($this->replacementAsNew->status),
            ]),
            'allocated_from' => $this->allocated_from?->toISOString(),
            'allocated_to' => $this->allocated_to?->toISOString(),
            'actual_returned_at' => $this->actual_returned_at?->toISOString(),
            'start_odometer' => $this->start_odometer === null ? null : $this->decimal($this->start_odometer),
            'end_odometer' => $this->end_odometer === null ? null : $this->decimal($this->end_odometer),
            'status' => $this->enumValue($this->status),
            'remarks' => $this->remarks,
            'drivers' => $this->loadedCollection('driverAssignments', fn ($assignment): array => [
                'id' => (int) $assignment->getKey(),
                'employee' => $assignment->relationLoaded('employee') && $assignment->employee ? array_merge(
                    $this->summary($assignment->employee, ['employee_number', 'first_name', 'last_name', 'display_name', 'default_hourly_rate', 'default_daily_rate']) ?? [],
                    [
                        'work_start_time' => $assignment->employee->metadata['work_start_time'] ?? null,
                        'work_end_time' => $assignment->employee->metadata['work_end_time'] ?? null,
                        'standard_shift_hours' => isset($assignment->employee->metadata['standard_shift_hours']) && $assignment->employee->metadata['standard_shift_hours'] !== null
                            ? (float) $assignment->employee->metadata['standard_shift_hours']
                            : null,
                    ]
                ) : null,
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
