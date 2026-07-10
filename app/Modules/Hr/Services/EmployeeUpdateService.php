<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\DTOs\UpdateEmployeeData;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrEmployee;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class EmployeeUpdateService
{
    public function __construct(private readonly DecimalMath $math, private readonly EmployeeValidationService $validator) {}

    public function update(HrEmployee $employee, UpdateEmployeeData $data): HrEmployee
    {
        return DB::transaction(function () use ($employee, $data): HrEmployee {
            $employee = HrEmployee::query()
                ->whereKey($employee->getKey())
                ->where('tenant_id', (int) $employee->tenant_id)
                ->when(
                    $employee->organization_unit_id === null,
                    static fn ($query) => $query->whereNull('organization_unit_id'),
                    static fn ($query) => $query->where('organization_unit_id', (int) $employee->organization_unit_id),
                )
                ->lockForUpdate()
                ->firstOrFail();

            if ($data->rowVersion !== (int) $employee->row_version) {
                throw new ConflictHttpException('Employee was changed by someone else. Reload before saving.');
            }

            if ($employee->status === EmployeeStatus::Terminated) {
                throw new InvalidArgumentException('Terminated employee master data cannot be updated directly.');
            }
            $this->validator->validateUpdate($employee, $data);

            $map = [
                'organization_unit_id' => $data->organizationUnitId, 'code' => $data->code,
                'first_name' => $data->firstName, 'middle_name' => $data->middleName, 'last_name' => $data->lastName,
                'display_name' => $data->displayName, 'email' => $data->email, 'phone' => $data->phone, 'mobile' => $data->mobile,
                'department_id' => $data->departmentId, 'designation_id' => $data->designationId,
                'employment_type_id' => $data->employmentTypeId, 'reporting_manager_id' => $data->reportingManagerId,
                'joined_date' => $data->joinedDate, 'resigned_date' => $data->resignedDate, 'date_of_birth' => $data->dateOfBirth,
                'gender' => $data->gender, 'notes' => $data->notes, 'metadata' => $data->metadata,
            ];
            foreach (['default_hourly_rate', 'default_daily_rate', 'default_service_rate'] as $key) {
                $property = match ($key) { 'default_hourly_rate' => 'defaultHourlyRate', 'default_daily_rate' => 'defaultDailyRate', default => 'defaultServiceRate' };
                $map[$key] = $data->{$property} === null ? null : $this->math->normalize($data->{$property});
            }
            $attributes = [];
            foreach ($map as $key => $value) {
                if (in_array($key, $data->provided, true)) {
                    $attributes[$key] = $value;
                }
            }
            $employee->fill($attributes);
            $employee->setAttribute('row_version', ((int) $employee->row_version) + 1);
            $employee->save();

            return $employee->refresh()->load(['department', 'designation', 'employmentType', 'reportingManager']);
        });
    }
}
