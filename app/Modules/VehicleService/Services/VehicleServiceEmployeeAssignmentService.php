<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceEmployeeAssignmentService
{
    use AssertsVehicleServiceExpectedVersion;

    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceCommissionService $commissions,
    ) {}

    public function create(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceEmployeeAssignmentData $data,
        ?int $expectedVersion = null,
    ): VehicleServiceLineEmployee {
        return DB::transaction(function () use ($job, $line, $data, $expectedVersion): VehicleServiceLineEmployee {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $line = $job->lines()->findOrFail($line->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->assertLine($job, $line);
            $this->validator->assertMutable($job);
            $this->validator->assertEmployeeAssignable($line);
            $this->validator->employee((int) $job->tenant_id, $job->organization_unit_id, $data->employeeId);

            $assignment = VehicleServiceLineEmployee::query()->create(array_merge($this->attributes($line, $data), [
                'tenant_id' => $job->tenant_id,
                'organization_unit_id' => $job->organization_unit_id,
                'vehicle_service_job_id' => $job->getKey(),
                'vehicle_service_job_line_id' => $line->getKey(),
                'assigned_at' => now(),
            ]))->load('employee');
            $this->bumpJobVersion($job);

            return $assignment;
        });
    }

    public function update(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceLineEmployee $assignment,
        VehicleServiceEmployeeAssignmentData $data,
        ?int $expectedVersion = null,
    ): VehicleServiceLineEmployee {
        return DB::transaction(function () use ($job, $line, $assignment, $data, $expectedVersion): VehicleServiceLineEmployee {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $line = $job->lines()->findOrFail($line->getKey());
            $assignment = $line->employeeAssignments()->findOrFail($assignment->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->assertAssignment($job, $line, $assignment);
            $this->validator->assertMutable($job);
            $this->validator->assertEmployeeAssignable($line);
            $this->validator->employee((int) $job->tenant_id, $job->organization_unit_id, $data->employeeId);

            $assignment->fill($this->attributes($line, $data));
            $assignment->completed_at = $data->status === 'completed' ? now() : null;
            $assignment->save();
            $this->bumpJobVersion($job);

            return $assignment->refresh()->load('employee');
        });
    }

    public function delete(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceLineEmployee $assignment,
        ?int $expectedVersion = null,
    ): void
    {
        DB::transaction(function () use ($job, $line, $assignment, $expectedVersion): void {
            $job = VehicleServiceJob::query()->lockForUpdate()->findOrFail($job->getKey());
            $line = $job->lines()->findOrFail($line->getKey());
            $assignment = $line->employeeAssignments()->findOrFail($assignment->getKey());
            $this->assertExpectedVersion($job, $expectedVersion);
            $this->assertAssignment($job, $line, $assignment);
            $this->validator->assertMutable($job);
            $assignment->delete();
            $this->bumpJobVersion($job);
        });
    }

    /** @return array<string, mixed> */
    private function attributes(VehicleServiceJobLine $line, VehicleServiceEmployeeAssignmentData $data): array
    {
        foreach ([$data->assignedHours, $data->rate, $data->commissionValue] as $value) {
            $this->validator->nonNegative($value, 'Employee assignment values cannot be negative.');
        }

        return [
            'employee_id' => $data->employeeId,
            'role_type' => $data->roleType,
            'assigned_hours' => $this->math->normalize($data->assignedHours),
            'rate' => $this->math->normalize($data->rate),
            'commission_type' => $data->commissionType->value,
            'commission_value' => $this->math->normalize($data->commissionValue),
            'commission_amount' => $this->commissions->calculate(
                $data->commissionType,
                $data->commissionValue,
                (string) $line->line_total,
            ),
            'status' => $data->status,
        ];
    }

    private function assertLine(VehicleServiceJob $job, VehicleServiceJobLine $line): void
    {
        if ((int) $line->vehicle_service_job_id !== (int) $job->getKey()) {
            throw new InvalidArgumentException('Service job line does not belong to the service job.');
        }
    }

    private function assertAssignment(VehicleServiceJob $job, VehicleServiceJobLine $line, VehicleServiceLineEmployee $assignment): void
    {
        $this->assertLine($job, $line);
        if ((int) $assignment->vehicle_service_job_id !== (int) $job->getKey()
            || (int) $assignment->vehicle_service_job_line_id !== (int) $line->getKey()) {
            throw new InvalidArgumentException('Employee assignment does not belong to the selected service job line.');
        }
    }
}
