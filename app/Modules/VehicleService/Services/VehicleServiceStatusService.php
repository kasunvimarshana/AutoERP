<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Services\VehicleStatusService;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceStatusHistory;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceStatusService
{
    use AssertsVehicleServiceExpectedVersion;

    private const VEHICLE_SERVICE_STARTED_REASON_PREFIX = 'Vehicle service started for job ';

    private const VEHICLE_SERVICE_RELEASED_REASON_PREFIX = 'Vehicle service completed or cancelled for job ';

    private const CANCELLED_ASSIGNMENT_STATUS = 'cancelled';

    private const WORKFORCE_REQUIRED_MESSAGE = 'Assign at least one labour employee before marking this job as inspected.';

    private const INVENTORY_ISSUE_REQUIRED_MESSAGE = 'Issue all required stock items before completing this job.';

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['inspected', 'in_progress', 'cancelled'],
        'inspected' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => ['invoiced', 'cancelled'],
        'invoiced' => ['partially_paid', 'paid'],
        'partially_paid' => ['paid'],
        'paid' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly VehicleStatusService $vehicleStatuses,
        private readonly VehicleServiceLineRuleService $lineRules,
    ) {}

    public function change(
        VehicleServiceJob $job,
        VehicleServiceJobStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
        ?int $expectedVersion = null,
    ): VehicleServiceJob {
        return DB::transaction(function () use ($job, $status, $changedBy, $reason, $expectedVersion): VehicleServiceJob {
            $snapshot = VehicleServiceJob::query()
                ->select(['id', 'tenant_id', 'organization_unit_id', 'vehicle_id'])
                ->findOrFail($job->getKey());
            $vehicle = Vehicle::query()
                ->where('tenant_id', $snapshot->tenant_id)
                ->lockForUpdate()
                ->findOrFail($snapshot->vehicle_id);
            $vehicleJobs = VehicleServiceJob::query()
                ->forContext((int) $snapshot->tenant_id, $snapshot->organization_unit_id)
                ->where('vehicle_id', $snapshot->vehicle_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $job = $vehicleJobs->firstWhere('id', $snapshot->getKey());
            if (! $job instanceof VehicleServiceJob) {
                throw new InvalidArgumentException('Vehicle service job changed while its vehicle timeline was being locked.');
            }
            $this->assertExpectedVersion($job, $expectedVersion);

            $old = $job->status;
            if ($old === $status) {
                return $job;
            }
            if (! in_array($status->value, self::TRANSITIONS[$old->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid service job status transition from {$old->value} to {$status->value}.");
            }

            if ($status === VehicleServiceJobStatus::Inspected) {
                $this->assertWorkforceReadyForInspection($job);
            }

            if ($status === VehicleServiceJobStatus::InProgress) {
                $this->assertVehicleCanEnterService($vehicle);
            }

            if ($status === VehicleServiceJobStatus::Completed) {
                $this->assertInventoryIssuedForCompletion($job);
            }

            $job->status = $status;
            if ($status === VehicleServiceJobStatus::Completed) {
                $job->completed_by = $changedBy;
                $job->completed_at = now();
                $job->lines()->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
                    ->update(['status' => VehicleServiceLineStatus::Completed->value]);
            }
            $job->save();

            $this->synchronizeVehicleStatus(
                $job,
                $vehicle,
                $vehicleJobs,
                $old,
                $status,
                $changedBy,
            );

            VehicleServiceStatusHistory::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'old_status' => $old->value,
                'new_status' => $status->value,
                'reason' => $reason,
                'changed_by' => $changedBy,
                'changed_at' => now(),
            ]);

            return $job->refresh();
        });
    }

    public function recordCreated(VehicleServiceJob $job, ?int $changedBy = null): void
    {
        VehicleServiceStatusHistory::query()->create([
            'tenant_id' => $job->tenant_id,
            'organization_unit_id' => $job->organization_unit_id,
            'vehicle_service_job_id' => $job->getKey(),
            'old_status' => null,
            'new_status' => VehicleServiceJobStatus::Draft->value,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    private function assertWorkforceReadyForInspection(VehicleServiceJob $job): void
    {
        $hasAssignableLabour = $job->lines()
            ->where('is_employee_assignable', true)
            ->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
            ->exists();

        if (! $hasAssignableLabour) {
            return;
        }

        $hasActiveAssignment = $job->lines()
            ->where('is_employee_assignable', true)
            ->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
            ->whereHas('employeeAssignments', fn ($query) => $query
                ->where('status', '!=', self::CANCELLED_ASSIGNMENT_STATUS))
            ->exists();

        if (! $hasActiveAssignment) {
            throw new InvalidArgumentException(self::WORKFORCE_REQUIRED_MESSAGE);
        }
    }

    private function assertVehicleCanEnterService(Vehicle $vehicle): void
    {
        if (! in_array($vehicle->status, [VehicleStatus::Active, VehicleStatus::UnderService], true)) {
            throw new InvalidArgumentException('Only an active vehicle can enter an in-progress service job.');
        }
    }

    private function assertInventoryIssuedForCompletion(VehicleServiceJob $job): void
    {
        $hasUnissuedInventory = $job->lines()
            ->with('item')
            ->whereNull('inventory_movement_id')
            ->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
            ->get()
            ->contains(fn (VehicleServiceJobLine $line): bool => $this->lineRules->isInventoryIssueLine($line));

        if ($hasUnissuedInventory) {
            throw new InvalidArgumentException(self::INVENTORY_ISSUE_REQUIRED_MESSAGE);
        }
    }

    /** @param Collection<int, VehicleServiceJob> $vehicleJobs */
    private function synchronizeVehicleStatus(
        VehicleServiceJob $job,
        Vehicle $vehicle,
        Collection $vehicleJobs,
        VehicleServiceJobStatus $from,
        VehicleServiceJobStatus $to,
        ?int $changedBy,
    ): void {
        if ($to === VehicleServiceJobStatus::InProgress && $vehicle->status === VehicleStatus::Active) {
            $this->vehicleStatuses->changeTo(
                $vehicle,
                VehicleStatus::UnderService,
                $changedBy,
                self::VEHICLE_SERVICE_STARTED_REASON_PREFIX.$job->job_number,
            );

            return;
        }

        if ($from !== VehicleServiceJobStatus::InProgress
            || ! in_array($to, [VehicleServiceJobStatus::Completed, VehicleServiceJobStatus::Cancelled], true)
            || $vehicle->status !== VehicleStatus::UnderService) {
            return;
        }

        $otherInProgressJobs = $vehicleJobs->contains(
            fn (VehicleServiceJob $candidate): bool => (int) $candidate->getKey() !== (int) $job->getKey()
                && $candidate->status === VehicleServiceJobStatus::InProgress,
        );
        if ($otherInProgressJobs) {
            return;
        }

        $this->vehicleStatuses->changeTo(
            $vehicle,
            VehicleStatus::Active,
            $changedBy,
            self::VEHICLE_SERVICE_RELEASED_REASON_PREFIX.$job->job_number,
        );
    }
}
