<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Hr\Models\HrEmployee;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class EmployeeQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        return $this->criteria($this->base($tenantId, $organizationUnitId), $criteria)
            ->with(['department', 'designation', 'employmentType'])
            ->orderBy('display_name')
            ->paginate($perPage);
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): HrEmployee
    {
        return $this->employee($id, $tenantId, $organizationUnitId)->load(['department', 'designation', 'employmentType', 'reportingManager']);
    }

    public function employee(int $id, int $tenantId, ?int $organizationUnitId): HrEmployee
    {
        return $this->base($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function delete(HrEmployee $employee, int $rowVersion): void
    {
        DB::transaction(function () use ($employee, $rowVersion): void {
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

            if ($rowVersion !== (int) $employee->row_version) {
                throw new ConflictHttpException('Employee was changed by someone else. Reload before deleting.');
            }

            $this->assertUnused($employee);

            $employee->delete();
        });
    }

    private function assertUnused(HrEmployee $employee): void
    {
        $tenantId = (int) $employee->tenant_id;
        $employeeId = (int) $employee->getKey();

        if (HrEmployee::query()->where('tenant_id', $tenantId)->where('reporting_manager_id', $employeeId)->exists()) {
            throw new ConflictHttpException('Employee cannot be deleted while other employees report to them.');
        }

        if (Schema::hasTable('vehicle_rental_running_charts')
            && DB::table('vehicle_rental_running_charts')
                ->where('tenant_id', $tenantId)
                ->where('driver_employee_id', $employeeId)
                ->exists()) {
            throw new ConflictHttpException('Employee cannot be deleted while assigned as driver in vehicle rental running charts.');
        }

        if (Schema::hasTable('vehicle_rental_reassignments')
            && DB::table('vehicle_rental_reassignments')
                ->where('tenant_id', $tenantId)
                ->where(function ($query) use ($employeeId): void {
                    $query->where('previous_driver_employee_id', $employeeId)
                        ->orWhere('new_driver_employee_id', $employeeId);
                })
                ->exists()) {
            throw new ConflictHttpException('Employee cannot be deleted while referenced in driver reassignments.');
        }

        if (Schema::hasTable('vehicle_service_jobs')
            && DB::table('vehicle_service_jobs')
                ->where('tenant_id', $tenantId)
                ->where('supervisor_employee_id', $employeeId)
                ->exists()) {
            throw new ConflictHttpException('Employee cannot be deleted while assigned as supervisor in service jobs.');
        }

        if (Schema::hasTable('vehicle_service_line_employees')
            && DB::table('vehicle_service_line_employees')
                ->where('tenant_id', $tenantId)
                ->where('employee_id', $employeeId)
                ->exists()) {
            throw new ConflictHttpException('Employee cannot be deleted while assigned to service job lines.');
        }
    }

    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage, string $kind = 'all'): LengthAwarePaginator
    {
        $query = $this->criteria($this->base($tenantId, $organizationUnitId), $criteria);
        if ($kind === 'active') { $query->active(); }
        if (in_array($kind, ['available', 'service-available'], true)) { $query->available(); }
        if ($kind === 'service-available') {
            $query->when(isset($criteria['at']), fn (Builder $q) => $q->whereDoesntHave('availabilities', function (Builder $availability) use ($criteria): void {
                $availability->whereIn('availability_status', ['assigned', 'on_leave', 'unavailable', 'suspended', 'inactive'])
                    ->where(fn (Builder $range) => $range->whereNull('starts_at')->orWhere('starts_at', '<=', $criteria['at']))
                    ->where(fn (Builder $range) => $range->whereNull('ends_at')->orWhere('ends_at', '>=', $criteria['at']));
            }));
        }
        return $query->with(['department', 'designation'])->orderBy('display_name')->paginate(min($perPage, 50));
    }

    private function base(int $tenantId, ?int $organizationUnitId): Builder
    {
        return HrEmployee::query()->forTenant($tenantId, $organizationUnitId);
    }

    private function criteria(Builder $query, array $criteria): Builder
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(fn (Builder $q) => $q->where('employee_number', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('display_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%"));
        }
        foreach (['department_id', 'designation_id', 'employment_type_id', 'status', 'availability_status'] as $key) {
            if (isset($criteria[$key]) && $criteria[$key] !== '') { $query->where($key, $criteria[$key]); }
        }
        if (isset($criteria['designation_code']) && $criteria['designation_code'] !== '') {
            $query->whereHas('designation', fn (Builder $q) => $q->where('code', $criteria['designation_code']));
        }
        if (isset($criteria['exclude_designation_code']) && $criteria['exclude_designation_code'] !== '') {
            $query->whereHas('designation', fn (Builder $q) => $q->where('code', '!=', $criteria['exclude_designation_code']));
        }
        if (isset($criteria['skill_id'])) { $query->whereHas('skillAssignments', fn (Builder $q) => $q->where('skill_id', $criteria['skill_id'])); }
        if (isset($criteria['certification_id'])) { $query->whereHas('certificationAssignments', fn (Builder $q) => $q->where('certification_id', $criteria['certification_id'])); }
        if (isset($criteria['license_id'])) { $query->whereHas('licenseAssignments', fn (Builder $q) => $q->where('license_id', $criteria['license_id'])); }
        return $query;
    }
}
