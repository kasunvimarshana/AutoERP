<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Application\Actions\DeleteHRRecordAction;
use Modules\HR\Application\Actions\FindHRRecordAction;
use Modules\HR\Application\Actions\ListHRRecordsAction;
use Modules\HR\Application\Actions\PersistHRRecordAction;
use Modules\HR\Application\DTOs\HRRecordData;
use Modules\HR\Application\Repositories\EmployeeRepositoryInterface;
use Modules\HR\Application\Repositories\LeaveAllocationRepositoryInterface;
use Modules\HR\Application\Repositories\LeaveApplicationRepositoryInterface;
use Modules\HR\Application\Repositories\PayrollRunRepositoryInterface;
use Modules\HR\Application\Repositories\PayslipLineRepositoryInterface;
use Modules\HR\Application\Repositories\PayslipRepositoryInterface;
use Modules\HR\Domain\Exceptions\HRIntegrityException;
use Modules\HR\Domain\Exceptions\HRRecordNotFoundException;
use Modules\HR\Domain\Services\HRDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class HRService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly LeaveAllocationRepositoryInterface $leaveAllocations,
        private readonly LeaveApplicationRepositoryInterface $leaveApplications,
        private readonly PayrollRunRepositoryInterface $payrollRuns,
        private readonly PayslipRepositoryInterface $payslips,
        private readonly PayslipLineRepositoryInterface $payslipLines,
        private readonly HRDomainService $domain,
        private readonly ListHRRecordsAction $listRecords,
        private readonly FindHRRecordAction $findRecord,
        private readonly PersistHRRecordAction $persistRecord,
        private readonly DeleteHRRecordAction $deleteRecord,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("hr.resources.{$key}");

        if (! is_array($definition)) {
            throw HRRecordNotFoundException::for('HR resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $resource, int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);

        return $this->listRecords->execute($this->repository($resource), ['tenant_id' => $tenantId, ...$filters], $perPage);
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $definition = $this->definition($resource);

        return $this->findRecord->execute($this->repository($resource), $definition['label'] ?? $resource, $tenantId, $id);
    }

    public function create(string $resource, HRRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $this->ensureTenantExists($data->tenantId);

        return $repository->transaction(function () use ($definition, $repository, $data): Model {
            $record = $this->persistRecord->create(
                $repository,
                $this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId),
            );
            $this->recalculateForResourceChange($definition['key'], $record, $data->tenantId);

            return $this->reloadRecord($definition['key'], $data->tenantId, $record->getKey());
        });
    }

    public function update(string $resource, int|string $tenantId, int|string $id, HRRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $repository->transaction(function () use ($definition, $repository, $record, $data, $tenantId): Model {
            $originalParent = $this->parentReference($definition['key'], $record);
            $updated = $this->persistRecord->update($repository, $record, [
                ...$this->prepareAttributes($definition['key'], $data->attributes, $tenantId),
                'row_version' => $this->domain->nextRowVersion($record),
            ]);

            if (! $this->sameParentReference($originalParent, $this->parentReference($definition['key'], $updated))) {
                $this->recalculateParentReference($tenantId, $originalParent);
            }

            $this->recalculateForResourceChange($definition['key'], $updated, $tenantId);

            return $this->reloadRecord($definition['key'], $tenantId, $updated->getKey());
        });
    }

    public function delete(string $resource, int|string $tenantId, int|string $id): bool
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);

        return $repository->transaction(function () use ($definition, $repository, $record, $tenantId): bool {
            $parent = $this->parentReference($definition['key'], $record);
            $deleted = $this->deleteRecord->execute($repository, $record);

            if ($deleted) {
                $this->recalculateParentReference($tenantId, $parent);
            }

            return $deleted;
        });
    }

    public function recalculateLeaveAllocation(int|string $tenantId, int|string $id): Model
    {
        $allocation = $this->find('leave_allocations', $tenantId, $id);
        $applications = $this->leaveApplications->getWhere([
            'tenant_id' => $tenantId,
            'employee_id' => $allocation->employee_id,
            'leave_type_id' => $allocation->leave_type_id,
        ]);

        return $this->leaveAllocations->update($allocation, [
            ...$this->domain->calculateLeaveAllocationUsage($applications),
            'row_version' => $this->domain->nextRowVersion($allocation),
        ]);
    }

    public function recalculatePayslip(int|string $tenantId, int|string $id): Model
    {
        $payslip = $this->find('payslips', $tenantId, $id);
        $lines = $this->payslipLines->getWhere(['tenant_id' => $tenantId, 'payslip_id' => $payslip->getKey()]);

        $updated = $this->payslips->update($payslip, [
            ...$this->domain->calculatePayslipTotals($payslip, $lines),
            'row_version' => $this->domain->nextRowVersion($payslip),
        ]);
        $this->recalculatePayrollRun($tenantId, $updated->payroll_run_id);

        return $updated;
    }

    public function recalculatePayrollRun(int|string $tenantId, int|string $id): Model
    {
        $payrollRun = $this->find('payroll_runs', $tenantId, $id);
        $payslips = $this->payslips->getWhere(['tenant_id' => $tenantId, 'payroll_run_id' => $payrollRun->getKey()]);
        $lines = $this->payslipLines->getWhere(['tenant_id' => $tenantId, 'payslip_id' => $payslips->pluck('id')->all()]);

        return $this->payrollRuns->update($payrollRun, [
            ...$this->domain->calculatePayrollRunTotals($payslips, $lines),
            'row_version' => $this->domain->nextRowVersion($payrollRun),
        ]);
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw HRRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw HRIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        foreach (config('hr.calculated_columns', []) as $calculatedColumn) {
            unset($attributes[$calculatedColumn]);
        }

        $attributes = [...$this->normalizeScalars($attributes), 'tenant_id' => $tenantId];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        $this->assertTenantParents($resource, $tenantId, $attributes);

        return match ($resource) {
            'departments' => $this->prepareDepartmentAttributes($attributes, $tenantId),
            'employees' => $this->prepareEmployeeAttributes($attributes),
            'attendance_logs' => $this->prepareAttendanceLogAttributes($attributes),
            'attendance_records' => $this->domain->prepareAttendanceRecord($attributes),
            'leave_applications' => $this->domain->prepareLeaveApplication($attributes),
            'salary_components', 'payslip_lines' => $this->prepareSalaryComponentTypeAttributes($attributes),
            'payroll_runs' => $this->preparePayrollRunAttributes($attributes),
            'payslips' => $this->preparePayslipAttributes($attributes),
            'performance_reviews' => $this->preparePerformanceReviewAttributes($attributes),
            default => $attributes,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeScalars(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $attributes[$key] = $this->domain->normalizeText($value);
            }
        }

        foreach (config('hr.decimal_columns', []) as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $attributes[$column] = $this->domain->normalizeDecimal($attributes[$column]);
            }
        }

        return $attributes;
    }

    private function prepareDepartmentAttributes(array $attributes, int|string $tenantId): array
    {
        $parentId = $attributes['parent_id'] ?? null;

        if ($parentId !== null) {
            $parent = $this->find('departments', $tenantId, $parentId);
            $attributes['depth'] = ((int) $parent->depth) + 1;
            $attributes['path'] = trim((string) ($parent->path ?? $parent->getKey()).'/'.($attributes['code'] ?? $attributes['name']), '/');
        }

        return $attributes;
    }

    private function prepareEmployeeAttributes(array $attributes): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('employee status', $attributes['status'] ?? null, config('hr.employee_statuses', []), 'active');

        return $attributes;
    }

    private function prepareAttendanceLogAttributes(array $attributes): array
    {
        $attributes['punch_type'] = $this->domain->normalizeEnum('punch type', $attributes['punch_type'] ?? null, config('hr.punch_types', []), 'in');

        return $attributes;
    }

    private function prepareSalaryComponentTypeAttributes(array $attributes): array
    {
        $attributes['type'] = $this->domain->normalizeEnum('salary component type', $attributes['type'] ?? null, config('hr.salary_component_types', []), 'earning');

        if (array_key_exists('calculation_type', $attributes)) {
            $attributes['calculation_type'] = $this->domain->normalizeEnum('calculation type', $attributes['calculation_type'], config('hr.calculation_types', []), 'fixed');
        }

        return $attributes;
    }

    private function preparePayrollRunAttributes(array $attributes): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('payroll status', $attributes['status'] ?? null, config('hr.payroll_statuses', []), 'draft');

        return $attributes;
    }

    private function preparePayslipAttributes(array $attributes): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('payslip status', $attributes['status'] ?? null, config('hr.payslip_statuses', []), 'draft');

        return $attributes;
    }

    private function preparePerformanceReviewAttributes(array $attributes): array
    {
        $attributes['status'] = $this->domain->normalizeEnum('review status', $attributes['status'] ?? null, config('hr.review_statuses', []), 'pending');

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertTenantParents(string $resource, int|string $tenantId, array $attributes): void
    {
        foreach ($this->parentRules($resource) as $column => $parentResource) {
            if (($attributes[$column] ?? null) !== null) {
                $this->find($parentResource, $tenantId, $attributes[$column]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function parentRules(string $resource): array
    {
        return match ($resource) {
            'employees' => ['department_id' => 'departments', 'designation_id' => 'designations', 'employment_type_id' => 'employment_types'],
            'employee_contacts', 'employee_documents', 'employee_contracts' => ['employee_id' => 'employees'],
            'attendance_logs' => ['employee_id' => 'employees', 'biometric_device_id' => 'biometric_devices'],
            'attendance_records' => ['employee_id' => 'employees', 'shift_id' => 'shifts'],
            'shift_assignments' => ['employee_id' => 'employees', 'shift_id' => 'shifts'],
            'leave_policy_lines' => ['leave_policy_id' => 'leave_policies', 'leave_type_id' => 'leave_types'],
            'leave_allocations', 'leave_applications' => ['employee_id' => 'employees', 'leave_type_id' => 'leave_types'],
            'salary_structure_lines' => ['salary_structure_id' => 'salary_structures', 'salary_component_id' => 'salary_components'],
            'employee_salary_assignments' => ['employee_id' => 'employees', 'salary_structure_id' => 'salary_structures'],
            'payslips' => ['employee_id' => 'employees', 'payroll_run_id' => 'payroll_runs', 'salary_structure_id' => 'salary_structures'],
            'payslip_lines' => ['payslip_id' => 'payslips', 'salary_component_id' => 'salary_components'],
            'performance_reviews' => ['employee_id' => 'employees', 'cycle_id' => 'performance_cycles'],
            default => [],
        };
    }

    private function recalculateForResourceChange(string $resource, Model $record, int|string $tenantId): void
    {
        match ($resource) {
            'leave_applications' => $this->recalculateMatchingLeaveAllocations($tenantId, $record),
            'leave_allocations' => $this->recalculateLeaveAllocation($tenantId, $record->getKey()),
            'payslips' => $this->recalculatePayslip($tenantId, $record->getKey()),
            'payslip_lines' => $this->recalculatePayslip($tenantId, $record->payslip_id),
            'payroll_runs' => $this->recalculatePayrollRun($tenantId, $record->getKey()),
            default => null,
        };
    }

    private function reloadRecord(string $resource, int|string $tenantId, int|string $id): Model
    {
        return $this->find($resource, $tenantId, $id);
    }

    /**
     * @return array{resource: string, id: int|string}|null
     */
    private function parentReference(string $resource, Model $record): ?array
    {
        return match ($resource) {
            'payslip_lines' => ['resource' => 'payslips', 'id' => $record->payslip_id],
            'payslips' => ['resource' => 'payroll_runs', 'id' => $record->payroll_run_id],
            'leave_applications' => ['resource' => 'leave_allocations_by_employee_type', 'id' => $record->employee_id.'|'.$record->leave_type_id],
            default => null,
        };
    }

    /**
     * @param  array{resource: string, id: int|string}|null  $parent
     */
    private function recalculateParentReference(int|string $tenantId, ?array $parent): void
    {
        if ($parent === null) {
            return;
        }

        if ($parent['resource'] === 'payslips') {
            $this->recalculatePayslip($tenantId, $parent['id']);
        }

        if ($parent['resource'] === 'payroll_runs') {
            $this->recalculatePayrollRun($tenantId, $parent['id']);
        }

        if ($parent['resource'] === 'leave_allocations_by_employee_type') {
            [$employeeId, $leaveTypeId] = explode('|', (string) $parent['id'], 2);
            $this->leaveAllocations->getWhere(['tenant_id' => $tenantId, 'employee_id' => $employeeId, 'leave_type_id' => $leaveTypeId])
                ->each(fn (Model $allocation) => $this->recalculateLeaveAllocation($tenantId, $allocation->getKey()));
        }
    }

    private function recalculateMatchingLeaveAllocations(int|string $tenantId, Model $application): void
    {
        $this->leaveAllocations->getWhere([
            'tenant_id' => $tenantId,
            'employee_id' => $application->employee_id,
            'leave_type_id' => $application->leave_type_id,
        ])->each(fn (Model $allocation) => $this->recalculateLeaveAllocation($tenantId, $allocation->getKey()));
    }

    /**
     * @param  array{resource: string, id: int|string}|null  $left
     * @param  array{resource: string, id: int|string}|null  $right
     */
    private function sameParentReference(?array $left, ?array $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        return $left['resource'] === $right['resource'] && (string) $left['id'] === (string) $right['id'];
    }
}

