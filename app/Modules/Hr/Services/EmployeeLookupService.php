<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Hr\DTOs\EmployeeResultData;
use Modules\Hr\Models\HrEmployee;

final class EmployeeLookupService
{
    public function activeEmployees(int $tenantId, ?int $organizationUnitId = null): Collection { return $this->base($tenantId, $organizationUnitId)->active()->get(); }
    public function availableEmployees(int $tenantId, ?int $organizationUnitId = null): Collection { return $this->base($tenantId, $organizationUnitId)->available()->get(); }
    public function employeesByDepartment(int $tenantId, int $id, ?int $organizationUnitId = null): Collection { return $this->base($tenantId, $organizationUnitId)->where('department_id', $id)->get(); }
    public function employeesByDesignation(int $tenantId, int $id, ?int $organizationUnitId = null): Collection { return $this->base($tenantId, $organizationUnitId)->where('designation_id', $id)->get(); }
    public function employeesBySkill(int $tenantId, int $id, ?int $organizationUnitId = null): Collection { return $this->relation($tenantId, $organizationUnitId, 'skillAssignments', 'skill_id', $id)->get(); }
    public function employeesByCertification(int $tenantId, int $id, ?int $organizationUnitId = null): Collection { return $this->relation($tenantId, $organizationUnitId, 'certificationAssignments', 'certification_id', $id)->get(); }
    public function employeesByLicense(int $tenantId, int $id, ?int $organizationUnitId = null): Collection { return $this->relation($tenantId, $organizationUnitId, 'licenseAssignments', 'license_id', $id)->get(); }
    public function techniciansBySkillOrDesignation(int $tenantId, ?int $skillId, ?int $designationId, ?int $organizationUnitId = null): Collection { return $this->base($tenantId, $organizationUnitId)->active()->when($skillId, fn (Builder $q) => $q->whereHas('skillAssignments', fn (Builder $r) => $r->where('skill_id', $skillId)))->when($designationId, fn (Builder $q) => $q->where('designation_id', $designationId))->get(); }
    public function employeesAvailableForVehicleService(int $tenantId, ?int $organizationUnitId = null, ?int $skillId = null, ?int $designationId = null): Collection { return $this->base($tenantId, $organizationUnitId)->available()->when($skillId, fn (Builder $q) => $q->whereHas('skillAssignments', fn (Builder $r) => $r->where('skill_id', $skillId)))->when($designationId, fn (Builder $q) => $q->where('designation_id', $designationId))->get(); }
    public function result(HrEmployee $employee): EmployeeResultData { return new EmployeeResultData((int) $employee->getKey(), (int) $employee->tenant_id, $employee->organization_unit_id, $employee->employee_number, $employee->display_name, $employee->status, $employee->availability_status); }
    private function base(int $tenantId, ?int $organizationUnitId): Builder { return HrEmployee::query()->forTenant($tenantId, $organizationUnitId); }
    private function relation(int $tenantId, ?int $organizationUnitId, string $relation, string $column, int $id): Builder { return $this->base($tenantId, $organizationUnitId)->whereHas($relation, fn (Builder $q) => $q->where($column, $id)); }
}
