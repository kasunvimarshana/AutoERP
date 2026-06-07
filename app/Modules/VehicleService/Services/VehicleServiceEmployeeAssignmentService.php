<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;

final class VehicleServiceEmployeeAssignmentService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceCommissionService $commissions,
    ) {}

    public function create(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceEmployeeAssignmentData $data,
    ): VehicleServiceLineEmployee {
        $this->assertLine($job, $line);
        $this->validator->assertMutable($job);
        $this->validator->assertEmployeeAssignable($line);
        $this->validator->employee((int) $job->tenant_id, $job->organization_unit_id, $data->employeeId);

        return VehicleServiceLineEmployee::query()->create(array_merge($this->attributes($line, $data), [
            'tenant_id' => $job->tenant_id,
            'organization_unit_id' => $job->organization_unit_id,
            'vehicle_service_job_id' => $job->getKey(),
            'vehicle_service_job_line_id' => $line->getKey(),
            'assigned_at' => now(),
        ]))->load('employee');
    }

    public function update(
        VehicleServiceJob $job,
        VehicleServiceJobLine $line,
        VehicleServiceLineEmployee $assignment,
        VehicleServiceEmployeeAssignmentData $data,
    ): VehicleServiceLineEmployee {
        $this->assertAssignment($job, $line, $assignment);
        $this->validator->assertMutable($job);
        $this->validator->assertEmployeeAssignable($line);
        $this->validator->employee((int) $job->tenant_id, $job->organization_unit_id, $data->employeeId);

        $assignment->fill($this->attributes($line, $data));
        $assignment->completed_at = $data->status === 'completed' ? now() : null;
        $assignment->save();

        return $assignment->refresh()->load('employee');
    }

    public function delete(VehicleServiceJob $job, VehicleServiceJobLine $line, VehicleServiceLineEmployee $assignment): void
    {
        $this->assertAssignment($job, $line, $assignment);
        $this->validator->assertMutable($job);
        $assignment->delete();
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
