<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Models\Customer;
use Modules\Hr\Models\HrDepartment;
use Modules\Hr\Models\HrDesignation;
use Modules\Hr\Models\HrEmployee;
use Modules\Invoice\Models\Invoice;
use Modules\Payment\Models\Payment;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;

final class EmployeeCommissionReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function run(array $params): array
    {
        $query = $this->filteredQuery($params);
        $metrics = $this->metrics(clone $query, (string) ($params['group_by'] ?? 'employee'));
        $this->applySorting($query, $params);

        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($params['per_page'] ?? 25)));

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query
            ->with($this->relations())
            ->paginate($perPage, ['vehicle_service_line_employees.*'], 'page', $page);

        $groupBy = (string) ($params['group_by'] ?? 'employee');

        return [
            'data' => $paginator->getCollection()
                ->map(fn (VehicleServiceLineEmployee $assignment): array => $this->row($assignment, $groupBy))
                ->values(),
            'summary' => $metrics['summary'],
            'rankings' => $metrics['rankings'],
            'groups' => $metrics['groups'],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'report' => $this->definition()->toArray(),
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(array $params): Collection
    {
        $query = $this->filteredQuery($params);
        $this->applySorting($query, $params);
        $groupBy = (string) ($params['group_by'] ?? 'employee');

        return $query
            ->with($this->relations())
            ->limit(5000)
            ->get(['vehicle_service_line_employees.*'])
            ->map(fn (VehicleServiceLineEmployee $assignment): array => $this->row($assignment, $groupBy));
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service/employee-commissions',
            title: 'Vehicle Service Employee Commission',
            group: 'Vehicle Service',
            model: VehicleServiceLineEmployee::class,
            description: 'Employee labour contribution and stored commission amounts from vehicle service assignments.',
            columns: [
                new ReportColumn('employee_code', 'Employee code', 'employee_code', 'employee'),
                new ReportColumn('employee_name', 'Employee name', 'employee_name', 'employee'),
                new ReportColumn('department_name', 'Department', 'department_name', 'department'),
                new ReportColumn('designation_name', 'Designation', 'designation_name', 'designation'),
                new ReportColumn('job_number', 'Job number', 'job_number', 'job_number'),
                new ReportColumn('job_date', 'Job date', 'job_date', 'job_date', 'date'),
                new ReportColumn('customer_name', 'Customer', 'customer_name', 'customer'),
                new ReportColumn('vehicle_label', 'Vehicle', 'vehicle_label', 'vehicle'),
                new ReportColumn('role_type', 'Role type', 'role_type', 'role_type'),
                new ReportColumn('assigned_hours', 'Assigned hours', 'assigned_hours', 'assigned_hours', 'decimal', true),
                new ReportColumn('rate', 'Rate', 'rate', 'rate', 'currency'),
                new ReportColumn('labour_amount', 'Labour amount', 'labour_amount', 'labour_amount', 'currency', true),
                new ReportColumn('commission_type', 'Commission type', 'commission_type', 'commission_type'),
                new ReportColumn('commission_value', 'Commission value', 'commission_value', 'commission_value', 'decimal'),
                new ReportColumn('commission_amount', 'Commission amount', 'commission_amount', 'commission_amount', 'currency', true),
                new ReportColumn('invoice_status', 'Invoice status'),
                new ReportColumn('payment_status', 'Payment status'),
                new ReportColumn('job_status', 'Job status', 'job_status', 'job_status'),
            ],
            dateColumn: 'job_date',
            defaultSort: 'job_date',
            defaultDirection: 'desc',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Builder<VehicleServiceLineEmployee>
     */
    private function filteredQuery(array $params): Builder
    {
        $tenantId = (int) $params['tenant_id'];
        $query = VehicleServiceLineEmployee::query()
            ->join('vehicle_service_jobs as jobs', 'jobs.id', '=', 'vehicle_service_line_employees.vehicle_service_job_id')
            ->join('vehicle_service_job_lines as lines', 'lines.id', '=', 'vehicle_service_line_employees.vehicle_service_job_line_id')
            ->leftJoin('hr_employees as employees', 'employees.id', '=', 'vehicle_service_line_employees.employee_id')
            ->leftJoin('hr_departments as departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('hr_designations as designations', 'designations.id', '=', 'employees.designation_id')
            ->leftJoin('hr_employees as supervisors', 'supervisors.id', '=', 'jobs.supervisor_employee_id')
            ->leftJoin('customers as report_customers', 'report_customers.id', '=', 'jobs.customer_id')
            ->leftJoin('vehicles as report_vehicles', 'report_vehicles.id', '=', 'jobs.vehicle_id')
            ->where('vehicle_service_line_employees.tenant_id', $tenantId)
            ->where('jobs.tenant_id', $tenantId)
            ->where('lines.tenant_id', $tenantId);

        if (array_key_exists('organization_unit_id', $params)) {
            $organizationUnitId = $params['organization_unit_id'];
            if ($organizationUnitId === null || $organizationUnitId === '') {
                $query
                    ->whereNull('vehicle_service_line_employees.organization_unit_id')
                    ->whereNull('jobs.organization_unit_id')
                    ->whereNull('lines.organization_unit_id');
            } else {
                $query
                    ->where('vehicle_service_line_employees.organization_unit_id', (int) $organizationUnitId)
                    ->where('jobs.organization_unit_id', (int) $organizationUnitId)
                    ->where('lines.organization_unit_id', (int) $organizationUnitId);
            }
        }

        if (! empty($params['date_from'])) {
            $query->whereDate('jobs.job_date', '>=', (string) $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('jobs.job_date', '<=', (string) $params['date_to']);
        }

        $this->whereInteger($query, 'vehicle_service_line_employees.employee_id', $params['employee_id'] ?? null);
        $this->whereInteger($query, 'employees.department_id', $params['department_id'] ?? null);
        $this->whereInteger($query, 'employees.designation_id', $params['designation_id'] ?? null);
        $this->whereInteger($query, 'jobs.supervisor_employee_id', $params['supervisor_id'] ?? null);
        $this->whereInteger($query, 'jobs.customer_id', $params['customer_id'] ?? null);
        $this->whereInteger($query, 'jobs.vehicle_id', $params['vehicle_id'] ?? null);
        $this->whereString($query, 'jobs.status', $params['job_status'] ?? null);
        $this->whereString($query, 'vehicle_service_line_employees.commission_type', $params['commission_type'] ?? null);

        if (! empty($params['invoice_status'])) {
            $status = (string) $params['invoice_status'];
            $query->whereHas('job.invoiceLinks.invoice', fn (Builder $invoice): Builder => $invoice
                ->where('invoices.tenant_id', $tenantId)
                ->where('status', $status));
        }
        if (! empty($params['payment_status'])) {
            $status = (string) $params['payment_status'];
            $query->whereHas('job.paymentLinks.payment', fn (Builder $payment): Builder => $payment
                ->where('payments.tenant_id', $tenantId)
                ->where('status', $status));
        }

        if (! empty($params['search'])) {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $params['search']).'%';
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('jobs.job_number', 'like', $search)
                    ->orWhere('lines.description', 'like', $search)
                    ->orWhere('employees.employee_number', 'like', $search)
                    ->orWhere('employees.display_name', 'like', $search)
                    ->orWhere('departments.name', 'like', $search)
                    ->orWhere('designations.name', 'like', $search)
                    ->orWhere('supervisors.display_name', 'like', $search)
                    ->orWhere('report_customers.customer_number', 'like', $search)
                    ->orWhere('report_customers.display_name', 'like', $search)
                    ->orWhere('report_customers.name', 'like', $search)
                    ->orWhere('report_vehicles.vehicle_number', 'like', $search)
                    ->orWhere('report_vehicles.registration_number', 'like', $search)
                    ->orWhere('vehicle_service_line_employees.role_type', 'like', $search);
            });
        }

        return $query->select('vehicle_service_line_employees.*');
    }

    /**
     * @param  Builder<VehicleServiceLineEmployee>  $query
     * @return array<string, mixed>
     */
    private function metrics(Builder $query, string $groupBy): array
    {
        $assignments = $query->with([
            'employee.department',
            'employee.designation',
            'job.supervisor',
        ])->get(['vehicle_service_line_employees.*']);

        $totalHours = '0.000000';
        $totalLabour = '0.000000';
        $totalCommission = '0.000000';
        $jobs = [];
        $employees = [];
        $groups = [];

        foreach ($assignments as $assignment) {
            $hours = $this->decimal($assignment->assigned_hours);
            $labour = $this->math->mul($hours, $this->decimal($assignment->rate));
            $commission = $this->decimal($assignment->commission_amount);
            $jobId = (int) $assignment->vehicle_service_job_id;
            $employeeId = (int) $assignment->employee_id;
            $group = $this->group($assignment, $groupBy);

            $totalHours = $this->math->add($totalHours, $hours);
            $totalLabour = $this->math->add($totalLabour, $labour);
            $totalCommission = $this->math->add($totalCommission, $commission);
            $jobs[$jobId] = true;

            $employees[$employeeId] ??= [
                'employee' => $this->employeeResource($assignment->employee),
                'labour_value' => '0.000000',
                'commission_amount' => '0.000000',
            ];
            $employees[$employeeId]['labour_value'] = $this->math->add($employees[$employeeId]['labour_value'], $labour);
            $employees[$employeeId]['commission_amount'] = $this->math->add($employees[$employeeId]['commission_amount'], $commission);

            $groups[$group['key']] ??= [
                'key' => $group['key'],
                'label' => $group['label'],
                'resource' => $group['resource'],
                'jobs' => [],
                'total_hours' => '0.000000',
                'total_labour_value' => '0.000000',
                'total_commission' => '0.000000',
            ];
            $groups[$group['key']]['jobs'][$jobId] = true;
            $groups[$group['key']]['total_hours'] = $this->math->add($groups[$group['key']]['total_hours'], $hours);
            $groups[$group['key']]['total_labour_value'] = $this->math->add($groups[$group['key']]['total_labour_value'], $labour);
            $groups[$group['key']]['total_commission'] = $this->math->add($groups[$group['key']]['total_commission'], $commission);
        }

        $totalJobs = count($jobs);
        $topEarning = $this->topEmployee($employees, 'labour_value');
        $topCommission = $this->topEmployee($employees, 'commission_amount');
        $groupRows = collect($groups)
            ->map(function (array $group): array {
                $group['total_jobs'] = count($group['jobs']);
                unset($group['jobs']);

                return $group;
            })
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return [
            'summary' => [
                'total_jobs' => $totalJobs,
                'total_hours' => $totalHours,
                'total_labour_value' => $totalLabour,
                'total_commission' => $totalCommission,
                'average_commission_per_job' => $totalJobs > 0
                    ? $this->math->div($totalCommission, (string) $totalJobs)
                    : '0.000000',
                'average_commission_per_hour' => $this->math->compare($totalHours, '0') > 0
                    ? $this->math->div($totalCommission, $totalHours)
                    : '0.000000',
            ],
            'rankings' => [
                'top_earning_employee' => $topEarning,
                'top_commission_employee' => $topCommission,
            ],
            'groups' => $groupRows,
        ];
    }

    /**
     * @param  Builder<VehicleServiceLineEmployee>  $query
     * @param  array<string, mixed>  $params
     */
    private function applySorting(Builder $query, array $params): void
    {
        $columns = [
            'employee' => 'employees.display_name',
            'department' => 'departments.name',
            'designation' => 'designations.name',
            'supervisor' => 'supervisors.display_name',
            'job_number' => 'jobs.job_number',
            'job_date' => 'jobs.job_date',
            'customer' => 'report_customers.display_name',
            'vehicle' => 'report_vehicles.registration_number',
            'role_type' => 'vehicle_service_line_employees.role_type',
            'assigned_hours' => 'vehicle_service_line_employees.assigned_hours',
            'rate' => 'vehicle_service_line_employees.rate',
            'labour_amount' => 'vehicle_service_line_employees.assigned_hours',
            'commission_type' => 'vehicle_service_line_employees.commission_type',
            'commission_value' => 'vehicle_service_line_employees.commission_value',
            'commission_amount' => 'vehicle_service_line_employees.commission_amount',
            'job_status' => 'jobs.status',
        ];
        $groupColumns = [
            'employee' => 'employees.display_name',
            'department' => 'departments.name',
            'designation' => 'designations.name',
            'supervisor' => 'supervisors.display_name',
        ];
        $sort = (string) ($params['sort'] ?? 'job_date');
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $groupBy = (string) ($params['group_by'] ?? '');

        if (isset($groupColumns[$groupBy])) {
            $query->orderBy($groupColumns[$groupBy]);
        }

        if ($sort === 'labour_amount') {
            $query
                ->orderByRaw("vehicle_service_line_employees.assigned_hours * vehicle_service_line_employees.rate {$direction}")
                ->orderBy('vehicle_service_line_employees.id');

            return;
        }

        $query
            ->orderBy($columns[$sort] ?? $columns['job_date'], $direction)
            ->orderBy('vehicle_service_line_employees.id');
    }

    /**
     * @return array<int, string>
     */
    private function relations(): array
    {
        return [
            'employee.department',
            'employee.designation',
            'line',
            'job.customer',
            'job.vehicle',
            'job.supervisor',
            'job.invoiceLinks.invoice',
            'job.paymentLinks.payment',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(VehicleServiceLineEmployee $assignment, string $groupBy): array
    {
        /** @var VehicleServiceJob|null $job */
        $job = $assignment->job;
        /** @var VehicleServiceJobLine|null $line */
        $line = $assignment->line;
        $employee = $assignment->employee;
        $invoice = $job instanceof VehicleServiceJob
            ? $job->invoiceLinks->sortByDesc('id')->pluck('invoice')->filter()->first()
            : null;
        $payment = $job instanceof VehicleServiceJob
            ? $job->paymentLinks->sortByDesc('id')->pluck('payment')->filter()->first()
            : null;
        $hours = $this->decimal($assignment->assigned_hours);
        $rate = $this->decimal($assignment->rate);
        $group = $this->group($assignment, $groupBy);

        return [
            'id' => (int) $assignment->getKey(),
            'employee' => $this->employeeResource($employee),
            'employee_code' => (string) ($employee?->employee_number ?? $employee?->code ?? ''),
            'employee_name' => (string) ($employee?->display_name ?? ''),
            'department' => $this->namedResource($employee?->department),
            'department_name' => (string) ($employee?->department?->name ?? ''),
            'designation' => $this->namedResource($employee?->designation),
            'designation_name' => (string) ($employee?->designation?->name ?? ''),
            'job' => $this->jobResource($job),
            'job_number' => (string) ($job?->job_number ?? ''),
            'job_date' => $job?->job_date?->toDateString(),
            'customer' => $this->customerResource($job?->customer),
            'customer_name' => (string) ($job?->customer?->display_name ?? $job?->customer?->name ?? ''),
            'vehicle' => $this->vehicleResource($job?->vehicle),
            'vehicle_label' => (string) ($job?->vehicle?->registration_number ?? $job?->vehicle?->vehicle_number ?? ''),
            'supervisor' => $this->employeeResource($job?->supervisor),
            'supervisor_name' => (string) ($job?->supervisor?->display_name ?? ''),
            'line_description' => (string) ($line?->description ?? ''),
            'role_type' => (string) $assignment->role_type,
            'assigned_hours' => $hours,
            'rate' => $rate,
            'labour_amount' => $this->math->mul($hours, $rate),
            'commission_type' => $this->enumValue($assignment->commission_type),
            'commission_value' => $this->decimal($assignment->commission_value),
            'commission_amount' => $this->decimal($assignment->commission_amount),
            'invoice' => $this->invoiceResource($invoice),
            'invoice_status' => $this->enumValue($invoice?->status),
            'payment' => $this->paymentResource($payment),
            'payment_status' => $this->enumValue($payment?->status),
            'job_status' => $this->enumValue($job?->status),
            'group_key' => $group['key'],
            'group_label' => $group['label'],
        ];
    }

    /**
     * @return array{key: string, label: string, resource: array<string, mixed>|null}
     */
    private function group(VehicleServiceLineEmployee $assignment, string $groupBy): array
    {
        $employee = $assignment->employee;
        $job = $assignment->job;
        $resource = match ($groupBy) {
            'department' => $this->namedResource($employee?->department),
            'designation' => $this->namedResource($employee?->designation),
            'supervisor' => $this->employeeResource($job?->supervisor),
            default => $this->employeeResource($employee),
        };

        return [
            'key' => $resource === null ? 'unassigned' : (string) $resource['id'],
            'label' => $resource === null ? 'Unassigned' : (string) $resource['name'],
            'resource' => $resource,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $employees
     * @return array<string, mixed>|null
     */
    private function topEmployee(array $employees, string $metric): ?array
    {
        $top = null;
        foreach ($employees as $employee) {
            if ($top === null || $this->math->compare((string) $employee[$metric], (string) $top[$metric]) > 0) {
                $top = $employee;
            }
        }

        return $top === null ? null : [
            'employee' => $top['employee'],
            'labour_value' => $top['labour_value'],
            'commission_amount' => $top['commission_amount'],
        ];
    }

    private function whereInteger(Builder $query, string $column, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            $query->where($column, (int) $value);
        }
    }

    private function whereString(Builder $query, string $column, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            $query->where($column, (string) $value);
        }
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return $value === null || $value === '' ? null : (string) $value;
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? '0'));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function employeeResource(?HrEmployee $employee): ?array
    {
        if (! $employee instanceof HrEmployee) {
            return null;
        }

        return [
            'id' => (int) $employee->getKey(),
            'code' => (string) ($employee->employee_number ?? $employee->code ?? ''),
            'name' => (string) ($employee->display_name ?? ''),
            'department' => $employee->relationLoaded('department') ? $this->namedResource($employee->department) : null,
            'designation' => $employee->relationLoaded('designation') ? $this->namedResource($employee->designation) : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function namedResource(HrDepartment|HrDesignation|null $resource): ?array
    {
        if ($resource === null) {
            return null;
        }

        return [
            'id' => (int) $resource->getKey(),
            'code' => (string) ($resource->code ?? ''),
            'name' => (string) ($resource->name ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function jobResource(?VehicleServiceJob $job): ?array
    {
        return $job === null ? null : [
            'id' => (int) $job->getKey(),
            'code' => (string) $job->job_number,
            'name' => (string) $job->job_number,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function customerResource(?Customer $customer): ?array
    {
        return $customer === null ? null : [
            'id' => (int) $customer->getKey(),
            'code' => (string) ($customer->customer_number ?? $customer->code ?? ''),
            'name' => (string) ($customer->display_name ?? $customer->name ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function vehicleResource(?Vehicle $vehicle): ?array
    {
        return $vehicle === null ? null : [
            'id' => (int) $vehicle->getKey(),
            'code' => (string) ($vehicle->vehicle_number ?? $vehicle->code ?? ''),
            'name' => (string) ($vehicle->registration_number ?? $vehicle->vehicle_number ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function invoiceResource(?Invoice $invoice): ?array
    {
        return $invoice === null ? null : [
            'id' => (int) $invoice->getKey(),
            'code' => (string) $invoice->invoice_number,
            'name' => (string) $invoice->invoice_number,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function paymentResource(?Payment $payment): ?array
    {
        return $payment === null ? null : [
            'id' => (int) $payment->getKey(),
            'code' => (string) $payment->payment_number,
            'name' => (string) $payment->payment_number,
        ];
    }
}
