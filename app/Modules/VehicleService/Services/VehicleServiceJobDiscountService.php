<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\VehicleService\DTOs\VehicleServiceJobDiscountData;
use Modules\VehicleService\Enums\VehicleServiceDiscountRevisionAction;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobDiscount;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceJobDiscountService
{
    use AssertsVehicleServiceExpectedVersion;

    public function __construct(
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceLineCalculationService $calculations,
    ) {}

    public function set(
        VehicleServiceJob $job,
        VehicleServiceJobDiscountData $data,
        ?int $expectedVersion = null,
    ): VehicleServiceJob {
        return DB::transaction(function () use ($job, $data, $expectedVersion): VehicleServiceJob {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->validator->assertMutable($job);
            $this->assertNoInvoice($job);

            $base = $this->calculations->discountBase($job);
            $amount = $this->calculations->discountAmount(
                $data->calculationType,
                $data->rate,
                $data->fixedAmount,
                $base,
            );
            $versionBefore = (int) $job->row_version;

            VehicleServiceJobDiscount::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'revision' => $this->nextRevision($job),
                'action' => VehicleServiceDiscountRevisionAction::Set->value,
                'calculation_type' => $data->calculationType->value,
                'rate' => $data->rate,
                'fixed_amount' => $data->fixedAmount,
                'calculation_base_snapshot' => $base,
                'calculated_amount_snapshot' => $amount,
                'reason' => $data->reason,
                'changed_by' => $data->changedBy,
                'changed_at' => now(),
            ]);

            $job = $this->calculations->recalculateJob($job);
            if ((int) $job->row_version === $versionBefore) {
                $job->forceFill(['row_version' => $versionBefore + 1])->save();
            }

            return $this->loadCurrent($job->refresh());
        });
    }

    public function remove(
        VehicleServiceJob $job,
        string $reason,
        ?int $changedBy = null,
        ?int $expectedVersion = null,
    ): VehicleServiceJob {
        return DB::transaction(function () use ($job, $reason, $changedBy, $expectedVersion): VehicleServiceJob {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->validator->assertMutable($job);
            $this->assertNoInvoice($job);

            $current = $job->discountRevisions()->first();
            if ($current === null || $current->action === VehicleServiceDiscountRevisionAction::Removed) {
                throw new InvalidArgumentException('This service job does not have a whole-job discount to remove.');
            }

            $versionBefore = (int) $job->row_version;

            VehicleServiceJobDiscount::query()->create([
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'revision' => $this->nextRevision($job),
                'action' => VehicleServiceDiscountRevisionAction::Removed->value,
                'calculation_type' => $current->calculation_type->value,
                'rate' => (string) $current->rate,
                'fixed_amount' => (string) $current->fixed_amount,
                'calculation_base_snapshot' => (string) $job->job_discount_base,
                'calculated_amount_snapshot' => (string) $job->job_discount_amount,
                'reason' => $reason,
                'changed_by' => $changedBy,
                'changed_at' => now(),
            ]);

            $job = $this->calculations->recalculateJob($job);
            if ((int) $job->row_version === $versionBefore) {
                $job->forceFill(['row_version' => $versionBefore + 1])->save();
            }

            return $this->loadCurrent($job->refresh());
        });
    }

    public function loadCurrent(VehicleServiceJob $job): VehicleServiceJob
    {
        return $job->load('currentDiscountRevision.changedBy');
    }

    private function nextRevision(VehicleServiceJob $job): int
    {
        return ((int) $job->discountRevisions()->max('revision')) + 1;
    }

    private function assertNoInvoice(VehicleServiceJob $job): void
    {
        if ($job->invoiceLinks()->where('status', 'active')->exists()) {
            throw new InvalidArgumentException('Whole-job discount cannot be changed after invoicing has started.');
        }
    }
}
