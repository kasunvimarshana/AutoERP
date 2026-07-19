<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\VehicleRental\Models\RentalDriverAssignment;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Models\RentalVehicleAllocation;

/**
 * Owns the concurrency boundary for recording one running-chart entry.
 *
 * The lower-level RentalUsageService retains validation, calculation-context,
 * event, fact, and persistence responsibilities. This command service acquires
 * shared resource timelines in one deterministic order before invoking it.
 */
final class RentalUsageCreationService
{
    public function __construct(private readonly RentalUsageService $usage) {}

    public function create(
        RentalVehicleAllocation $allocation,
        array $data,
        ?int $userId,
    ): RentalUsageLog {
        return DB::transaction(function () use ($allocation, $data, $userId): RentalUsageLog {
            $snapshot = RentalVehicleAllocation::query()
                ->select(['id', 'tenant_id', 'vehicle_id'])
                ->findOrFail($allocation->getKey());

            $this->lockVehicleTimeline(
                (int) $snapshot->tenant_id,
                (int) $snapshot->vehicle_id,
            );

            $current = RentalVehicleAllocation::query()
                ->select([
                    'id',
                    'tenant_id',
                    'organization_unit_id',
                    'vehicle_id',
                    'source_allocation_id',
                    'row_version',
                ])
                ->findOrFail($allocation->getKey());

            $this->assertTimelineIdentity($snapshot, $current);
            $this->assertSourceUsesLockedTimeline($current);
            $this->lockDriverTimeline($current, $data);

            return $this->usage->create($current, $data, $userId);
        });
    }

    private function lockVehicleTimeline(int $tenantId, int $vehicleId): void
    {
        RentalVehicleAllocation::query()
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    private function assertTimelineIdentity(
        RentalVehicleAllocation $snapshot,
        RentalVehicleAllocation $current,
    ): void {
        if (
            (int) $snapshot->tenant_id !== (int) $current->tenant_id
            || (int) $snapshot->vehicle_id !== (int) $current->vehicle_id
        ) {
            throw ValidationException::withMessages([
                'expected_allocation_version' => [
                    'The vehicle allocation changed while the running chart was being prepared. Reload and review the latest version.',
                ],
            ]);
        }
    }

    private function assertSourceUsesLockedTimeline(RentalVehicleAllocation $allocation): void
    {
        if ($allocation->source_allocation_id === null) {
            return;
        }

        $source = RentalVehicleAllocation::query()
            ->select(['id', 'tenant_id', 'vehicle_id'])
            ->where('tenant_id', $allocation->tenant_id)
            ->findOrFail($allocation->source_allocation_id);

        if ((int) $source->vehicle_id !== (int) $allocation->vehicle_id) {
            throw ValidationException::withMessages([
                'expected_source_allocation_version' => [
                    'The owner supply allocation must use the same vehicle as the customer allocation.',
                ],
            ]);
        }
    }

    private function lockDriverTimeline(
        RentalVehicleAllocation $allocation,
        array $data,
    ): void {
        $assignmentId = isset($data['driver_assignment_id'])
            ? (int) $data['driver_assignment_id']
            : null;

        if ($assignmentId === null) {
            return;
        }

        $snapshot = RentalDriverAssignment::query()
            ->select(['id', 'tenant_id', 'vehicle_allocation_id', 'employee_id'])
            ->where('tenant_id', $allocation->tenant_id)
            ->where('vehicle_allocation_id', $allocation->getKey())
            ->findOrFail($assignmentId);

        RentalDriverAssignment::query()
            ->where('tenant_id', $allocation->tenant_id)
            ->where('employee_id', $snapshot->employee_id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);

        $current = RentalDriverAssignment::query()
            ->select(['id', 'tenant_id', 'vehicle_allocation_id', 'employee_id'])
            ->where('tenant_id', $allocation->tenant_id)
            ->where('vehicle_allocation_id', $allocation->getKey())
            ->findOrFail($assignmentId);

        if ((int) $current->employee_id !== (int) $snapshot->employee_id) {
            throw ValidationException::withMessages([
                'driver_assignment_id' => [
                    'The driver assignment changed while the running chart was being prepared. Reload and review the latest assignment.',
                ],
            ]);
        }
    }
}
