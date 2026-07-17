<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Enums\ItemType;
use Modules\VehicleService\DTOs\VehicleServiceEmployeeAssignmentData;
use Modules\VehicleService\Enums\VehicleServiceCommissionType;
use Modules\VehicleService\Enums\VehicleServiceWorkforceRole;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;
use Modules\VehicleService\Services\Concerns\AssertsVehicleServiceExpectedVersion;

final class VehicleServiceEmployeeAssignmentService
{
    use AssertsVehicleServiceExpectedVersion;

    private const ZERO_AMOUNT = '0.000000';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly VehicleServiceValidationService $validator,
        private readonly VehicleServiceCommissionService $commissions,
        private readonly VehicleServiceCommissionPolicyService $commissionPolicies,
        private readonly VehicleServiceLineCalculationService $calculations,
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
            ]));
            $this->calculations->recalculateAssignments($line);
            $this->bumpJobVersion($job);

            return $assignment->refresh()->load('employee');
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

            $assignment->fill($this->attributes($line, $data, $assignment));
            $assignment->completed_at = $data->status === 'completed' ? now() : null;
            $assignment->save();
            $this->calculations->recalculateAssignments($line);
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
            $this->calculations->recalculateAssignments($line);
            $this->bumpJobVersion($job);
        });
    }

    /** @return array<string, mixed> */
    private function attributes(
        VehicleServiceJobLine $line,
        VehicleServiceEmployeeAssignmentData $data,
        ?VehicleServiceLineEmployee $existing = null,
    ): array {
        foreach ([$data->assignedHours, $data->rate] as $value) {
            $this->validator->nonNegative($value, 'Employee assignment values cannot be negative.');
        }
        $role = VehicleServiceWorkforceRole::tryFrom($data->roleType);
        if (! $role instanceof VehicleServiceWorkforceRole) {
            throw new InvalidArgumentException('Invalid Vehicle Service workforce role.');
        }
        $commission = $this->commission($line, $data, $existing);

        return [
            'employee_id' => $data->employeeId,
            'role_type' => $role->value,
            'assigned_hours' => $this->math->normalize($data->assignedHours),
            'rate' => $this->math->normalize($data->rate),
            'commission_type' => $commission['type']->value,
            'commission_value' => $commission['value'],
            'commission_amount' => $this->commissions->calculate(
                $commission['type'],
                $commission['value'],
                (string) $line->line_total,
            ),
            'status' => $data->status,
        ];
    }

    /** @return array{type: VehicleServiceCommissionType, value: string} */
    private function commission(
        VehicleServiceJobLine $line,
        VehicleServiceEmployeeAssignmentData $data,
        ?VehicleServiceLineEmployee $existing,
    ): array {
        if ($data->commissionType !== null) {
            if ($data->commissionValue === null) {
                throw new InvalidArgumentException('Employee commission value is required.');
            }

            return [
                'type' => $data->commissionType,
                'value' => $this->math->normalize($data->commissionValue),
            ];
        }

        if ($data->commissionValue !== null) {
            throw new InvalidArgumentException('Employee commission type is required.');
        }

        if ($existing instanceof VehicleServiceLineEmployee) {
            return [
                'type' => $existing->commission_type,
                'value' => (string) $existing->commission_value,
            ];
        }

        $line->loadMissing('item');
        if ($line->item?->item_type !== ItemType::Labour || $line->organization_unit_id === null) {
            return ['type' => VehicleServiceCommissionType::None, 'value' => self::ZERO_AMOUNT];
        }

        return $this->commissionPolicies->resolveLaborRule(
            (int) $line->tenant_id,
            (int) $line->organization_unit_id,
            (int) $line->item_id,
        );
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
