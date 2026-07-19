<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalDriverAssignmentStatus;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalReplacementStatus;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Models\RentalVehicleReplacement;

final class RentalReplacementService
{
    private const PRESERVE_AGREEMENT_BILLING_PERIOD = 'continue_period';

    public function __construct(
        private readonly RentalNumberService $numbers,
        private readonly RentalAllocationService $allocations,
        private readonly RentalCustodyService $custody,
        private readonly RentalStatusHistoryService $history,
    ) {}

    public function replace(RentalVehicleAllocation $oldAllocation, array $data, ?int $userId): RentalVehicleReplacement
    {
        return DB::transaction(function () use ($oldAllocation, $data, $userId): RentalVehicleReplacement {
            $oldAllocation = RentalVehicleAllocation::query()
                ->with(['agreement', 'driverAssignments'])
                ->lockForUpdate()
                ->findOrFail($oldAllocation->getKey());
            $this->assertAllocationExpectedVersion($oldAllocation, (int) $data['expected_allocation_version']);
            if ($oldAllocation->agreement->agreement_kind !== RentalAgreementKind::CustomerRental
                || $oldAllocation->status !== RentalAllocationStatus::Active) {
                throw new InvalidArgumentException('Only an active customer allocation can be replaced.');
            }
            if ((int) $oldAllocation->vehicle_id === (int) $data['new_vehicle_id']) {
                throw new InvalidArgumentException('Replacement vehicle must differ from the current vehicle.');
            }

            $replacement = RentalVehicleReplacement::query()->create([
                'tenant_id' => $oldAllocation->tenant_id,
                'organization_unit_id' => $oldAllocation->organization_unit_id,
                'replacement_number' => $data['replacement_number'] ?? $this->numbers->next(
                    (int) $oldAllocation->tenant_id,
                    $oldAllocation->organization_unit_id,
                    'vehicle_rental_replacement',
                    'RVR-',
                ),
                'agreement_id' => $oldAllocation->agreement_id,
                'old_allocation_id' => $oldAllocation->getKey(),
                'new_allocation_id' => null,
                'replacement_at' => $data['replacement_at'],
                'reason_code' => $data['reason_code'] ?? null,
                'reason' => $data['reason'] ?? null,
                'billing_continuity_rule' => self::PRESERVE_AGREEMENT_BILLING_PERIOD,
                'status' => RentalReplacementStatus::Completed->value,
                'completed_by' => $userId,
                'completed_at' => now(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $returnData = array_merge($data['old_return'] ?? [], [
                'event_type' => RentalCustodyEventType::ReplacementOut->value,
                'occurred_at' => $data['replacement_at'],
                'replacement_id' => $replacement->getKey(),
                'expected_allocation_version' => (int) $oldAllocation->row_version,
                'from_role' => 'customer',
                'to_role' => 'company',
            ]);
            $returnEvent = $this->custody->create($oldAllocation, $returnData, $userId);
            $this->custody->confirm(
                $returnEvent,
                (int) $returnEvent->row_version,
                (int) $oldAllocation->row_version,
                $userId,
            );

            $newAllocation = $this->allocations->create($oldAllocation->agreement, [
                'expected_agreement_version' => $data['expected_agreement_version'],
                'vehicle_id' => $data['new_vehicle_id'],
                'vehicle_ownership_id' => $data['vehicle_ownership_id'] ?? null,
                'vehicle_source_type' => $data['vehicle_source_type'],
                'source_allocation_id' => $data['source_allocation_id'] ?? null,
                'expected_source_allocation_version' => $data['expected_source_allocation_version'] ?? null,
                'vehicle_finance_agreement_id' => $data['vehicle_finance_agreement_id'] ?? null,
                'expected_finance_agreement_version' => $data['expected_finance_agreement_version'] ?? null,
                'replaces_allocation_id' => $oldAllocation->getKey(),
                'allocated_from' => $data['replacement_at'],
                'allocated_to' => $data['allocated_to'] ?? $oldAllocation->agreement->ends_at->toDateTimeString(),
                'drivers' => $data['drivers'] ?? $this->replacementDrivers($oldAllocation, $data),
                'remarks' => $data['remarks'] ?? null,
            ], $userId);

            $replacement->new_allocation_id = $newAllocation->getKey();
            $replacement->save();

            $handoverData = array_merge($data['new_handover'] ?? [], [
                'event_type' => RentalCustodyEventType::ReplacementIn->value,
                'occurred_at' => $data['replacement_at'],
                'replacement_id' => $replacement->getKey(),
                'expected_allocation_version' => (int) $newAllocation->row_version,
                'from_role' => 'company',
                'to_role' => 'customer',
            ]);
            $handoverEvent = $this->custody->create($newAllocation, $handoverData, $userId);
            $this->custody->confirm(
                $handoverEvent,
                (int) $handoverEvent->row_version,
                (int) $newAllocation->row_version,
                $userId,
            );

            $this->history->record(
                $replacement,
                null,
                RentalReplacementStatus::Completed->value,
                $userId,
            );

            return $replacement->refresh()->load([
                'agreement.customer', 'oldAllocation.vehicle', 'newAllocation.vehicle',
                'oldAllocation.custodyEvents.items', 'newAllocation.custodyEvents.items',
            ]);
        });
    }

    private function assertAllocationExpectedVersion(RentalVehicleAllocation $allocation, int $expectedVersion): void
    {
        if ((int) $allocation->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_allocation_version' => ['The vehicle allocation changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function replacementDrivers(RentalVehicleAllocation $oldAllocation, array $data): array
    {
        return $oldAllocation->driverAssignments
            ->filter(fn ($assignment): bool => in_array($assignment->status, [
                RentalDriverAssignmentStatus::Planned,
                RentalDriverAssignmentStatus::Active,
            ], true))
            ->map(fn ($assignment): array => [
                'employee_id' => $assignment->employee_id,
                'assignment_role' => $assignment->assignment_role,
                'is_primary' => $assignment->is_primary,
                'assigned_from' => $data['replacement_at'],
                'assigned_to' => $data['allocated_to'] ?? $oldAllocation->agreement->ends_at->toDateTimeString(),
            ])->values()->all();
    }
}
