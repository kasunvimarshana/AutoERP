<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\Models\HrEmployee;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;

final class EmployeeIncentiveReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly OperationalReportResponseBuilder $responses,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(array $params): array
    {
        $query = $this->query($params);
        $summary = $this->summary(clone $query);
        $this->sort($query, $params);

        return $this->responses->paginate(
            $query,
            fn (object $row): array => $this->row($row),
            $this->definition(),
            $summary,
            max(1, (int) ($params['page'] ?? 1)),
            min(100, max(1, (int) ($params['per_page'] ?? 25))),
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(array $params): Collection
    {
        $query = $this->query($params);
        $this->sort($query, $params);

        return $this->responses->exportRows($query, fn (object $row): array => $this->row($row));
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service/employee-incentives',
            title: 'Employee Incentive Report',
            group: 'Vehicle Service',
            model: HrEmployee::class,
            description: 'Technician and supervisor incentives using the stored Vehicle Service commission bases and amounts.',
            columns: [
                new ReportColumn('employee_code', 'Employee code', sortBy: 'employee_code'),
                new ReportColumn('employee_name', 'Employee', sortBy: 'employee_name'),
                new ReportColumn('department', 'Department', sortBy: 'department'),
                new ReportColumn('designation', 'Designation', sortBy: 'designation'),
                new ReportColumn('incentive_source', 'Source', sortBy: 'incentive_source'),
                new ReportColumn('job_number', 'Job number', sortBy: 'job_number'),
                new ReportColumn('job_date', 'Job date', sortBy: 'job_date', format: 'date'),
                new ReportColumn('job_status', 'Job status', sortBy: 'job_status'),
                new ReportColumn('customer', 'Customer'),
                new ReportColumn('vehicle', 'Vehicle'),
                new ReportColumn('work_description', 'Work / responsibility'),
                new ReportColumn('assigned_hours', 'Assigned hours', sortBy: 'assigned_hours', format: 'decimal', summarize: true),
                new ReportColumn('rate', 'Rate', sortBy: 'rate', format: 'money'),
                new ReportColumn('commission_base', 'Commission base', sortBy: 'commission_base', format: 'money', summarize: true),
                new ReportColumn('commission_type', 'Commission type', sortBy: 'commission_type'),
                new ReportColumn('commission_value', 'Commission value', sortBy: 'commission_value', format: 'decimal'),
                new ReportColumn('incentive_amount', 'Incentive amount', sortBy: 'incentive_amount', format: 'money', summarize: true),
                new ReportColumn('assignment_status', 'Assignment status'),
                new ReportColumn('completed_at', 'Completed at', sortBy: 'completed_at', format: 'datetime'),
            ],
            dateColumn: 'job_date',
            defaultSort: 'job_date',
            defaultDirection: 'desc',
            orientation: 'landscape',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function query(array $params): Builder
    {
        $tenantId = (int) $params['tenant_id'];
        $organizationUnitId = $params['organization_unit_id'] ?? null;

        $technicians = DB::table('vehicle_service_line_employees as assignments')
            ->join('vehicle_service_jobs as jobs', 'jobs.id', '=', 'assignments.vehicle_service_job_id')
            ->join('vehicle_service_job_lines as lines', 'lines.id', '=', 'assignments.vehicle_service_job_line_id')
            ->join('hr_employees as employees', 'employees.id', '=', 'assignments.employee_id')
            ->leftJoin('hr_departments as departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('hr_designations as designations', 'designations.id', '=', 'employees.designation_id')
            ->leftJoin('customers', 'customers.id', '=', 'jobs.customer_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'jobs.vehicle_id')
            ->whereNull('jobs.deleted_at')
            ->whereNull('employees.deleted_at')
            ->where('assignments.tenant_id', $tenantId)
            ->where('jobs.tenant_id', $tenantId)
            ->where(function (Builder $query): void {
                $query->where('assignments.commission_type', '<>', 'none')
                    ->orWhere('assignments.commission_amount', '>', 0);
            })
            ->selectRaw(
                "'technician' as incentive_source, assignments.id as source_id, assignments.employee_id, jobs.id as job_id, lines.id as line_id, "
                .'employees.employee_number as employee_code, employees.display_name as employee_name, '
                .'departments.id as department_id, departments.code as department_code, departments.name as department_name, '
                .'designations.code as designation_code, designations.name as designation_name, '
                .'jobs.job_number, jobs.job_date, jobs.status as job_status, '
                .'customers.code as customer_code, customers.name as customer_name, '
                .'vehicles.vehicle_number, vehicles.registration_number, '
                .'lines.description as work_description, assignments.assigned_hours, assignments.rate, '
                .'lines.line_total as commission_base, assignments.commission_type, assignments.commission_value, '
                .'assignments.commission_amount as incentive_amount, assignments.status as assignment_status, '
                .'COALESCE(assignments.completed_at, jobs.completed_at) as completed_at'
            );
        $this->organizationScope($technicians, 'assignments.organization_unit_id', $organizationUnitId);
        $this->organizationScope($technicians, 'jobs.organization_unit_id', $organizationUnitId);

        $supervisors = DB::table('vehicle_service_jobs as jobs')
            ->join('hr_employees as employees', 'employees.id', '=', 'jobs.supervisor_employee_id')
            ->leftJoin('hr_departments as departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('hr_designations as designations', 'designations.id', '=', 'employees.designation_id')
            ->leftJoin('customers', 'customers.id', '=', 'jobs.customer_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'jobs.vehicle_id')
            ->whereNull('jobs.deleted_at')
            ->whereNull('employees.deleted_at')
            ->where('jobs.tenant_id', $tenantId)
            ->whereNotNull('jobs.supervisor_employee_id')
            ->where(function (Builder $query): void {
                $query->where('jobs.supervisor_commission_type', '<>', 'none')
                    ->orWhere('jobs.supervisor_commission_amount', '>', 0);
            })
            ->selectRaw(
                "'supervisor' as incentive_source, jobs.id as source_id, jobs.supervisor_employee_id as employee_id, jobs.id as job_id, 0 as line_id, "
                .'employees.employee_number as employee_code, employees.display_name as employee_name, '
                .'departments.id as department_id, departments.code as department_code, departments.name as department_name, '
                .'designations.code as designation_code, designations.name as designation_name, '
                .'jobs.job_number, jobs.job_date, jobs.status as job_status, '
                .'customers.code as customer_code, customers.name as customer_name, '
                .'vehicles.vehicle_number, vehicles.registration_number, '
                ."'Job supervision' as work_description, 0 as assigned_hours, 0 as rate, "
                .'jobs.grand_total as commission_base, jobs.supervisor_commission_type as commission_type, '
                .'jobs.supervisor_commission_value as commission_value, jobs.supervisor_commission_amount as incentive_amount, '
                ."jobs.status as assignment_status, jobs.completed_at as completed_at"
            );
        $this->organizationScope($supervisors, 'jobs.organization_unit_id', $organizationUnitId);

        $union = $technicians->unionAll($supervisors);
        $query = DB::query()->fromSub($union, 'incentives');

        if (! empty($params['date_from'])) {
            $query->whereDate('incentives.job_date', '>=', (string) $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('incentives.job_date', '<=', (string) $params['date_to']);
        }
        if (! empty($params['job_status'])) {
            $query->where('incentives.job_status', (string) $params['job_status']);
        }
        if (! empty($params['incentive_source'])) {
            $query->where('incentives.incentive_source', (string) $params['incentive_source']);
        }
        if (! empty($params['employee_id'])) {
            $query->where('incentives.employee_id', (int) $params['employee_id']);
        }
        if (! empty($params['department_id'])) {
            $query->where('incentives.department_id', (int) $params['department_id']);
        }
        if (! empty($params['customer_id'])) {
            $query->whereExists(function (Builder $customer) use ($params): void {
                    $customer->selectRaw('1')
                        ->from('vehicle_service_jobs as scoped_jobs')
                        ->whereColumn('scoped_jobs.id', 'incentives.job_id')
                        ->where('scoped_jobs.customer_id', (int) $params['customer_id']);
                });
        }
        if (! empty($params['vehicle_id'])) {
            $query->whereExists(function (Builder $vehicle) use ($params): void {
                $vehicle->selectRaw('1')
                    ->from('vehicle_service_jobs as scoped_jobs')
                    ->whereColumn('scoped_jobs.id', 'incentives.job_id')
                    ->where('scoped_jobs.vehicle_id', (int) $params['vehicle_id']);
            });
        }

        $search = $this->searchTerm($params['search'] ?? null);
        if ($search !== null) {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('incentives.employee_code', 'like', $search)
                    ->orWhere('incentives.employee_name', 'like', $search)
                    ->orWhere('incentives.department_name', 'like', $search)
                    ->orWhere('incentives.designation_name', 'like', $search)
                    ->orWhere('incentives.job_number', 'like', $search)
                    ->orWhere('incentives.customer_name', 'like', $search)
                    ->orWhere('incentives.vehicle_number', 'like', $search)
                    ->orWhere('incentives.registration_number', 'like', $search)
                    ->orWhere('incentives.work_description', 'like', $search);
            });
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Builder $query): array
    {
        $totals = $query->selectRaw(
            'COUNT(*) as total_entries, COUNT(DISTINCT employee_id) as total_employees, '
            .'COUNT(DISTINCT job_id) as total_jobs, COALESCE(SUM(assigned_hours), 0) as total_hours, '
            .'COALESCE(SUM(commission_base), 0) as total_commission_base, '
            .'COALESCE(SUM(incentive_amount), 0) as total_incentive'
        )->first();

        $hours = $this->decimal($totals->total_hours ?? 0);
        $incentive = $this->decimal($totals->total_incentive ?? 0);
        $jobs = (int) ($totals->total_jobs ?? 0);

        return [
            'total_entries' => (int) ($totals->total_entries ?? 0),
            'total_employees' => (int) ($totals->total_employees ?? 0),
            'total_jobs' => $jobs,
            'total_hours' => $hours,
            'total_commission_base' => $this->decimal($totals->total_commission_base ?? 0),
            'total_incentive' => $incentive,
            'average_incentive_per_job' => $jobs > 0 ? $this->math->div($incentive, (string) $jobs) : $this->decimal(0),
            'average_incentive_per_hour' => $this->math->compare($hours, '0') > 0
                ? $this->math->div($incentive, $hours)
                : $this->decimal(0),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function sort(Builder $query, array $params): void
    {
        $columns = [
            'employee_code' => 'incentives.employee_code',
            'employee_name' => 'incentives.employee_name',
            'department' => 'incentives.department_name',
            'designation' => 'incentives.designation_name',
            'incentive_source' => 'incentives.incentive_source',
            'job_number' => 'incentives.job_number',
            'job_date' => 'incentives.job_date',
            'job_status' => 'incentives.job_status',
            'assigned_hours' => 'incentives.assigned_hours',
            'rate' => 'incentives.rate',
            'commission_base' => 'incentives.commission_base',
            'commission_type' => 'incentives.commission_type',
            'commission_value' => 'incentives.commission_value',
            'incentive_amount' => 'incentives.incentive_amount',
            'completed_at' => 'incentives.completed_at',
        ];
        $sort = (string) ($params['sort'] ?? 'job_date');
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query
            ->orderBy($columns[$sort] ?? $columns['job_date'], $direction)
            ->orderBy('incentives.job_id', 'desc')
            ->orderBy('incentives.employee_name');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(object $row): array
    {
        return [
            'id' => (string) $row->incentive_source.'-'.(string) $row->source_id,
            'employee_id' => (int) $row->employee_id,
            'employee_code' => (string) ($row->employee_code ?? ''),
            'employee_name' => (string) ($row->employee_name ?? ''),
            'department' => trim((string) ($row->department_code ?? '').' '.(string) ($row->department_name ?? '')),
            'designation' => trim((string) ($row->designation_code ?? '').' '.(string) ($row->designation_name ?? '')),
            'incentive_source' => (string) $row->incentive_source,
            'job_number' => (string) $row->job_number,
            'job_date' => (string) $row->job_date,
            'job_status' => (string) $row->job_status,
            'customer' => trim((string) ($row->customer_code ?? '').' '.(string) ($row->customer_name ?? '')),
            'vehicle' => trim((string) ($row->vehicle_number ?? '').' '.(string) ($row->registration_number ?? '')),
            'work_description' => (string) ($row->work_description ?? ''),
            'assigned_hours' => $this->decimal($row->assigned_hours),
            'rate' => $this->decimal($row->rate),
            'commission_base' => $this->decimal($row->commission_base),
            'commission_type' => (string) $row->commission_type,
            'commission_value' => $this->decimal($row->commission_value),
            'incentive_amount' => $this->decimal($row->incentive_amount),
            'assignment_status' => (string) ($row->assignment_status ?? ''),
            'completed_at' => $row->completed_at === null ? null : (string) $row->completed_at,
        ];
    }

    private function organizationScope(Builder $query, string $column, mixed $organizationUnitId): void
    {
        if ($organizationUnitId === null || $organizationUnitId === '') {
            $query->whereNull($column);
            return;
        }

        $query->where($column, (int) $organizationUnitId);
    }

    private function searchTerm(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return '%'.str_replace(['%', '_'], ' ', $value).'%';
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? '0'));
    }
}
