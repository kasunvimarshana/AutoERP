<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\VehicleService\Enums\VehicleServiceJobStatus;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceStatusHistory;

final class VehicleServiceStatusService
{
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

    public function change(
        VehicleServiceJob $job,
        VehicleServiceJobStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
    ): VehicleServiceJob {
        $old = $job->status;
        if ($old === $status) {
            return $job;
        }
        if (! in_array($status->value, self::TRANSITIONS[$old->value] ?? [], true)) {
            throw new InvalidArgumentException("Invalid service job status transition from {$old->value} to {$status->value}.");
        }

        $job->status = $status;
        if ($status === VehicleServiceJobStatus::Completed) {
            $job->completed_by = $changedBy;
            $job->completed_at = now();
            $job->lines()->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
                ->update(['status' => VehicleServiceLineStatus::Completed->value]);
        }
        $job->save();

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
}
