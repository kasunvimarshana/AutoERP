<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\DTOs\CreateEmployeeData;
use Modules\Hr\DTOs\UpdateEmployeeData;
use Modules\Hr\Models\HrDepartment;
use Modules\Hr\Models\HrDesignation;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmploymentType;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class EmployeeValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function validateCreate(CreateEmployeeData $data): void
    {
        if (trim($data->firstName) === '' || trim($data->displayName) === '') {
            throw new InvalidArgumentException('Employee first name and display name are required.');
        }
        if ($data->employeeNumber !== null) {
            $this->assertNumberUnique($data->tenantId, $data->employeeNumber);
        }
        if ($data->code !== null) {
            $this->assertCodeUnique($data->tenantId, $data->code);
        }
        $this->assertOrganization($data->tenantId, $data->organizationUnitId);
        $this->assertReferences($data->tenantId, $data->organizationUnitId, $data->departmentId, $data->designationId, $data->employmentTypeId, $data->reportingManagerId);
        $this->assertDates($data->joinedDate, $data->resignedDate);
        $this->assertRates($data->defaultHourlyRate, $data->defaultDailyRate, $data->defaultServiceRate);
    }

    public function validateUpdate(HrEmployee $employee, UpdateEmployeeData $data): void
    {
        if ($data->code !== null) {
            $this->assertCodeUnique((int) $employee->tenant_id, $data->code, (int) $employee->getKey());
        }
        if ($data->reportingManagerId !== null && $data->reportingManagerId === (int) $employee->getKey()) {
            throw new InvalidArgumentException('Employee cannot report to self.');
        }
        $organizationUnitId = in_array('organization_unit_id', $data->provided, true) ? $data->organizationUnitId : $employee->organization_unit_id;
        $this->assertOrganization((int) $employee->tenant_id, $organizationUnitId);
        $this->assertReferences(
            (int) $employee->tenant_id,
            $organizationUnitId,
            in_array('department_id', $data->provided, true) ? $data->departmentId : $employee->department_id,
            in_array('designation_id', $data->provided, true) ? $data->designationId : $employee->designation_id,
            in_array('employment_type_id', $data->provided, true) ? $data->employmentTypeId : $employee->employment_type_id,
            in_array('reporting_manager_id', $data->provided, true) ? $data->reportingManagerId : $employee->reporting_manager_id,
        );
        $this->assertDates(
            in_array('joined_date', $data->provided, true) ? $data->joinedDate : $employee->joined_date?->toDateString(),
            in_array('resigned_date', $data->provided, true) ? $data->resignedDate : $employee->resigned_date?->toDateString(),
        );
        foreach ([$data->defaultHourlyRate, $data->defaultDailyRate, $data->defaultServiceRate] as $rate) {
            if ($rate !== null && $this->math->isNegative($rate)) {
                throw new InvalidArgumentException('Employee rates cannot be negative.');
            }
        }
    }

    public function assertScopedActive(Model $model, HrEmployee $employee, string $label): void
    {
        $this->assertScope((int) $employee->tenant_id, $employee->organization_unit_id, (int) $model->tenant_id, $model->organization_unit_id);
        if (! (bool) $model->is_active) {
            throw new InvalidArgumentException("Inactive {$label} cannot be assigned.");
        }
    }

    public function assertScope(int $tenantId, ?int $organizationUnitId, int $recordTenantId, ?int $recordOrganizationUnitId): void
    {
        if ($tenantId !== $recordTenantId) {
            throw new InvalidArgumentException('HR reference belongs to a different tenant.');
        }
        if ($organizationUnitId !== null && $recordOrganizationUnitId !== null && $organizationUnitId !== (int) $recordOrganizationUnitId) {
            throw new InvalidArgumentException('HR reference belongs to a different organization unit.');
        }
    }

    public function assertDateRange(?string $from, ?string $to, string $message = 'The end date must not be before the start date.'): void
    {
        if ($from !== null && $to !== null && $to < $from) {
            throw new InvalidArgumentException($message);
        }
    }

    private function assertReferences(int $tenantId, ?int $organizationUnitId, ?int $departmentId, ?int $designationId, ?int $employmentTypeId, ?int $managerId): void
    {
        foreach ([
            [$departmentId, HrDepartment::class, 'department'],
            [$designationId, HrDesignation::class, 'designation'],
            [$employmentTypeId, HrEmploymentType::class, 'employment type'],
            [$managerId, HrEmployee::class, 'reporting manager'],
        ] as [$id, $class, $label]) {
            if ($id === null) {
                continue;
            }
            $model = $this->findReference($class, $id, $tenantId);
            $this->assertScope($tenantId, $organizationUnitId, (int) $model->tenant_id, $model->organization_unit_id);
            if ($model instanceof HrEmployee) {
                if ($model->status->value === 'terminated') {
                    throw new InvalidArgumentException('Terminated employee cannot be a reporting manager.');
                }
            } elseif (! (bool) $model->is_active) {
                throw new InvalidArgumentException("Inactive {$label} cannot be used.");
            }
        }
    }

    private function assertOrganization(int $tenantId, ?int $organizationUnitId): void
    {
        if ($organizationUnitId === null) {
            return;
        }
        $organization = OrganizationUnitModel::query()->find($organizationUnitId);
        if (! $organization instanceof OrganizationUnitModel) {
            if (DB::table('organization_units')
                ->where('id', $organizationUnitId)
                ->where('tenant_id', '<>', $tenantId)
                ->exists()) {
                throw new InvalidArgumentException('Employee organization unit must be active and belong to the tenant.');
            }

            throw (new ModelNotFoundException)->setModel(OrganizationUnitModel::class, [$organizationUnitId]);
        }
        if ((int) $organization->tenant_id !== $tenantId || ! (bool) $organization->is_active) {
            throw new InvalidArgumentException('Employee organization unit must be active and belong to the tenant.');
        }
    }

    /**
     * @param class-string<Model> $class
     */
    private function findReference(string $class, int $id, int $tenantId): Model
    {
        $model = $class::query()->find($id);
        if ($model instanceof Model) {
            return $model;
        }

        $table = (new $class)->getTable();
        if (DB::table($table)
            ->where('id', $id)
            ->where('tenant_id', '<>', $tenantId)
            ->exists()) {
            throw new InvalidArgumentException('HR reference belongs to a different tenant.');
        }

        throw (new ModelNotFoundException)->setModel($class, [$id]);
    }

    private function assertDates(?string $joinedDate, ?string $resignedDate): void
    {
        $this->assertDateRange($joinedDate, $resignedDate, 'Employee resigned date cannot be before joined date.');
    }

    private function assertRates(string ...$rates): void
    {
        foreach ($rates as $rate) {
            if ($this->math->isNegative($rate)) {
                throw new InvalidArgumentException('Employee rates cannot be negative.');
            }
        }
    }

    private function assertNumberUnique(int $tenantId, string $number): void
    {
        if (HrEmployee::query()->withTrashed()->where('tenant_id', $tenantId)->where('employee_number', $number)->exists()) {
            throw new InvalidArgumentException('Employee number already exists for this tenant.');
        }
    }

    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void
    {
        $query = HrEmployee::query()->withTrashed()->where('tenant_id', $tenantId)->where('code', $code);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        if ($query->exists()) {
            throw new InvalidArgumentException('Employee code already exists for this tenant.');
        }
    }
}
