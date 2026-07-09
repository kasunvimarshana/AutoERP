<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\VehicleService\Enums\VehicleServiceBillingStatus;
use Modules\VehicleService\Enums\VehicleServiceLifecycleDimension;
use Modules\VehicleService\Enums\VehicleServiceLineStatus;
use Modules\VehicleService\Enums\VehicleServiceOperationalStatus;
use Modules\VehicleService\Enums\VehicleServicePaymentStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceStatusHistory;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceStatusService
{
    use AssertsVehicleServiceExpectedVersion;

    /** @var array<string, list<string>> */
    private const OPERATIONAL_TRANSITIONS = [
        'draft' => ['inspected', 'in_progress', 'cancelled'],
        'inspected' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => ['cancelled'],
        'cancelled' => [],
    ];

    /** @var array<string, list<string>> */
    private const BILLING_TRANSITIONS = [
        'unbilled' => ['partially_billed', 'billed'],
        'partially_billed' => ['billed'],
        'billed' => [],
    ];

    /** @var array<string, list<string>> */
    private const PAYMENT_TRANSITIONS = [
        'unpaid' => ['partially_paid', 'paid'],
        'partially_paid' => ['paid'],
        'paid' => [],
    ];

    public function changeOperational(
        VehicleServiceJob $job,
        VehicleServiceOperationalStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
        ?int $expectedVersion = null,
    ): VehicleServiceJob {
        return DB::transaction(function () use ($job, $status, $changedBy, $reason, $expectedVersion): VehicleServiceJob {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);

            $old = $job->operational_status;
            if ($old === $status) {
                return $job;
            }
            if (! in_array($status->value, self::OPERATIONAL_TRANSITIONS[$old->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid service job operational status transition from {$old->value} to {$status->value}.");
            }

            $job->operational_status = $status;
            if ($status === VehicleServiceOperationalStatus::Completed) {
                $job->completed_by = $changedBy;
                $job->completed_at = now();
                $job->lines()->where('status', '!=', VehicleServiceLineStatus::Cancelled->value)
                    ->update(['status' => VehicleServiceLineStatus::Completed->value]);
            }
            $job->save();

            $this->record(
                $job,
                VehicleServiceLifecycleDimension::Operational,
                $old->value,
                $status->value,
                $changedBy,
                $reason,
            );

            return $job->refresh();
        });
    }

    public function changeBilling(
        VehicleServiceJob $job,
        VehicleServiceBillingStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
        ?int $expectedVersion = null,
    ): VehicleServiceJob {
        return DB::transaction(function () use ($job, $status, $changedBy, $reason, $expectedVersion): VehicleServiceJob {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);

            $old = $job->billing_status;
            if ($old === $status) {
                return $job;
            }
            if (! in_array($status->value, self::BILLING_TRANSITIONS[$old->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid service job billing status transition from {$old->value} to {$status->value}.");
            }

            $job->billing_status = $status;
            $job->save();

            $this->record(
                $job,
                VehicleServiceLifecycleDimension::Billing,
                $old->value,
                $status->value,
                $changedBy,
                $reason,
            );

            return $job->refresh();
        });
    }

    public function changePayment(
        VehicleServiceJob $job,
        VehicleServicePaymentStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
        ?int $expectedVersion = null,
    ): VehicleServiceJob {
        return DB::transaction(function () use ($job, $status, $changedBy, $reason, $expectedVersion): VehicleServiceJob {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);

            $old = $job->payment_status;
            if ($old === $status) {
                return $job;
            }
            if (! in_array($status->value, self::PAYMENT_TRANSITIONS[$old->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid service job payment status transition from {$old->value} to {$status->value}.");
            }

            $job->payment_status = $status;
            $job->save();

            $this->record(
                $job,
                VehicleServiceLifecycleDimension::Payment,
                $old->value,
                $status->value,
                $changedBy,
                $reason,
            );

            return $job->refresh();
        });
    }

    public function recordCreated(VehicleServiceJob $job, ?int $changedBy = null): void
    {
        $this->record(
            $job,
            VehicleServiceLifecycleDimension::Operational,
            null,
            VehicleServiceOperationalStatus::Draft->value,
            $changedBy,
        );
        $this->record(
            $job,
            VehicleServiceLifecycleDimension::Billing,
            null,
            VehicleServiceBillingStatus::Unbilled->value,
            $changedBy,
        );
        $this->record(
            $job,
            VehicleServiceLifecycleDimension::Payment,
            null,
            VehicleServicePaymentStatus::Unpaid->value,
            $changedBy,
        );
    }

    private function record(
        VehicleServiceJob $job,
        VehicleServiceLifecycleDimension $dimension,
        ?string $oldStatus,
        string $newStatus,
        ?int $changedBy = null,
        ?string $reason = null,
    ): void {
        VehicleServiceStatusHistory::query()->create([
            'tenant_id' => $job->tenant_id,
            'organization_unit_id' => $job->organization_unit_id,
            'vehicle_service_job_id' => $job->getKey(),
            'dimension' => $dimension->value,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
