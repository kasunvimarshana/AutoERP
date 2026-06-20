<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleService\Models\VehicleServiceLineEmployee;

final class EmployeeCommissionReportService
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
        $metrics = $this->metrics(clone $query, (string) ($params['group_by'] ?? 'employee'));
        $this->sort($query, $params);

        $groupBy = (string) ($params['group_by'] ?? 'employee');
        $response = $this->responses->paginate(
            $query,
            fn (object $row): array => $this->row($row, $groupBy),
            $this->definition(),
            $metrics['summary'],
            max(1, (int) ($params['page'] ?? 1)),
            min(100, max(1, (int) ($params['per_page'] ?? 25))),
        );

        $response['rankings'] = $metrics['rankings'];
        $response['groups'] = $metrics['groups'];

        return $response;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(array $params): Collection
    {
        $query = $this->query($params);
        $this->sort($query, $params);

        $groupBy = (string) ($params['group_by'] ?? 'employee');

        return $this->responses->exportRows($query, fn (object $row): array => $this->row($row, $groupBy));
    }

    public function definition(): ReportDefinition
    {
        return new ReportDefinition(
            key: 'vehicle-service/employee-commissions',
            title: 'Employee Commission Report',
            group: 'Vehicle Service',
            model: VehicleServiceLineEmployee::class,
            description: 'Technician and supervisor commissions from Vehicle Service jobs using the stored domain-calculated bases and amounts.',
            columns: [
                new ReportColumn('employee_code', 'Employee code', sortBy: 'employee_code'),
                new ReportColumn('employee_name', 'Employee', sortBy: 'employee_name'),
                new ReportColumn('department_name', 'Department', sortBy: 'department'),
                new ReportColumn('designation_name', 'Designation', sortBy: 'designation'),
                new ReportColumn('commission_source', 'Source', sortBy: 'commission_source'),
                new ReportColumn('role_type', 'Role', sortBy: 'role_type'),
                new ReportColumn('job_number', 'Job number', sortBy: 'job_number'),
                new ReportColumn('job_date', 'Job date', sortBy: 'job_date', format: 'date'),
                new ReportColumn('customer_name', 'Customer', sortBy: 'customer'),
                new ReportColumn('vehicle_label', 'Vehicle', sortBy: 'vehicle'),
                new ReportColumn('work_description', 'Work / responsibility'),
                new ReportColumn('assigned_hours', 'Assigned hours', sortBy: 'assigned_hours', format: 'decimal', summarize: true),
                new ReportColumn('rate', 'Rate', sortBy: 'rate', format: 'money'),
                new ReportColumn('labour_amount', 'Labour value', sortBy: 'labour_amount', format: 'money', summarize: true),
                new ReportColumn('commission_base', 'Commission base', sortBy: 'commission_base', format: 'money', summarize: true),
                new ReportColumn('commission_type', 'Commission type', sortBy: 'commission_type'),
                new ReportColumn('commission_value', 'Commission value', sortBy: 'commission_value', format: 'decimal'),
                new ReportColumn('commission_amount', 'Commission amount', sortBy: 'commission_amount', format: 'money', summarize: true),
                new ReportColumn('commission_status', 'Commission status', sortBy: 'commission_status'),
                new ReportColumn('invoice_progress', 'Invoice progress'),
                new ReportColumn('payment_progress', 'Payment progress'),
                new ReportColumn('job_status', 'Job status', sortBy: 'job_status'),
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
        $includeCancelled = filter_var($params['include_cancelled'] ?? false, FILTER_VALIDATE_BOOL);

        $technicians = DB::table('vehicle_service_line_employees as assignments')
            ->join('vehicle_service_jobs as jobs', 'jobs.id', '=', 'assignments.vehicle_service_job_id')
            ->join('vehicle_service_job_lines as lines', 'lines.id', '=', 'assignments.vehicle_service_job_line_id')
            ->join('hr_employees as employees', 'employees.id', '=', 'assignments.employee_id')
            ->leftJoin('hr_departments as departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('hr_designations as designations', 'designations.id', '=', 'employees.designation_id')
            ->leftJoin('hr_employees as supervisors', 'supervisors.id', '=', 'jobs.supervisor_employee_id')
            ->leftJoin('customers', 'customers.id', '=', 'jobs.customer_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'jobs.vehicle_id')
            ->whereNull('jobs.deleted_at')
            ->where('assignments.tenant_id', $tenantId)
            ->where('jobs.tenant_id', $tenantId)
            ->where('lines.tenant_id', $tenantId)
            ->where('employees.tenant_id', $tenantId)
            ->where(function (Builder $commission): void {
                $commission->where('assignments.commission_type', '<>', 'none')
                    ->orWhere('assignments.commission_amount', '>', 0);
            });
        $this->organizationScope($technicians, 'assignments.organization_unit_id', $organizationUnitId);
        $this->organizationScope($technicians, 'jobs.organization_unit_id', $organizationUnitId);
        $this->organizationScope($technicians, 'lines.organization_unit_id', $organizationUnitId);
        if (! $includeCancelled) {
            $technicians
                ->where('jobs.status', '<>', 'cancelled')
                ->where('lines.status', '<>', 'cancelled');
        }
        $technicians->selectRaw(
            "'technician' as commission_source, assignments.id as source_id, assignments.employee_id, jobs.id as job_id, lines.id as line_id, "
            .'employees.employee_number as employee_code, employees.display_name as employee_name, '
            .'departments.id as department_id, departments.code as department_code, departments.name as department_name, '
            .'designations.id as designation_id, designations.code as designation_code, designations.name as designation_name, '
            .'jobs.supervisor_employee_id as job_supervisor_id, supervisors.employee_number as job_supervisor_code, supervisors.display_name as job_supervisor_name, '
            .'jobs.job_number, jobs.job_date, jobs.status as job_status, '
            .'customers.id as customer_id, customers.customer_number as customer_code, COALESCE(customers.display_name, customers.name) as customer_name, '
            .'vehicles.id as vehicle_id, vehicles.vehicle_number as vehicle_code, COALESCE(vehicles.registration_number, vehicles.vehicle_number) as vehicle_label, '
            .'lines.description as work_description, assignments.role_type, assignments.assigned_hours, assignments.rate, '
            .'(assignments.assigned_hours * assignments.rate) as labour_amount, lines.line_total as commission_base, '
            .'assignments.commission_type, assignments.commission_value, assignments.commission_amount, '
            ."CASE WHEN jobs.status = 'cancelled' OR lines.status = 'cancelled' THEN 'cancelled' "
            ."WHEN assignments.completed_at IS NOT NULL OR assignments.status = 'completed' OR jobs.completed_at IS NOT NULL "
            ."OR jobs.status IN ('completed', 'invoiced', 'partially_paid', 'paid') THEN 'earned' ELSE 'pending' END as commission_status, "
            .'COALESCE(assignments.completed_at, jobs.completed_at) as completed_at'
        );

        $supervisors = DB::table('vehicle_service_jobs as jobs')
            ->join('hr_employees as employees', 'employees.id', '=', 'jobs.supervisor_employee_id')
            ->leftJoin('hr_departments as departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('hr_designations as designations', 'designations.id', '=', 'employees.designation_id')
            ->leftJoin('customers', 'customers.id', '=', 'jobs.customer_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'jobs.vehicle_id')
            ->whereNull('jobs.deleted_at')
            ->where('jobs.tenant_id', $tenantId)
            ->where('employees.tenant_id', $tenantId)
            ->whereNotNull('jobs.supervisor_employee_id')
            ->where(function (Builder $commission): void {
                $commission->where('jobs.supervisor_commission_type', '<>', 'none')
                    ->orWhere('jobs.supervisor_commission_amount', '>', 0);
            });
        $this->organizationScope($supervisors, 'jobs.organization_unit_id', $organizationUnitId);
        if (! $includeCancelled) {
            $supervisors->where('jobs.status', '<>', 'cancelled');
        }
        $supervisors->selectRaw(
            "'supervisor' as commission_source, jobs.id as source_id, jobs.supervisor_employee_id as employee_id, jobs.id as job_id, 0 as line_id, "
            .'employees.employee_number as employee_code, employees.display_name as employee_name, '
            .'departments.id as department_id, departments.code as department_code, departments.name as department_name, '
            .'designations.id as designation_id, designations.code as designation_code, designations.name as designation_name, '
            .'jobs.supervisor_employee_id as job_supervisor_id, employees.employee_number as job_supervisor_code, employees.display_name as job_supervisor_name, '
            .'jobs.job_number, jobs.job_date, jobs.status as job_status, '
            .'customers.id as customer_id, customers.customer_number as customer_code, COALESCE(customers.display_name, customers.name) as customer_name, '
            .'vehicles.id as vehicle_id, vehicles.vehicle_number as vehicle_code, COALESCE(vehicles.registration_number, vehicles.vehicle_number) as vehicle_label, '
            ."'Job supervision' as work_description, 'supervisor' as role_type, 0 as assigned_hours, 0 as rate, 0 as labour_amount, "
            .'jobs.grand_total as commission_base, jobs.supervisor_commission_type as commission_type, '
            .'jobs.supervisor_commission_value as commission_value, jobs.supervisor_commission_amount as commission_amount, '
            ."CASE WHEN jobs.status = 'cancelled' THEN 'cancelled' WHEN jobs.completed_at IS NOT NULL "
            ."OR jobs.status IN ('completed', 'invoiced', 'partially_paid', 'paid') THEN 'earned' ELSE 'pending' END as commission_status, "
            .'jobs.completed_at as completed_at'
        );

        $commissionRows = $technicians->unionAll($supervisors);
        $invoiceTotals = $this->invoiceTotals($tenantId, $organizationUnitId);
        $paymentTotals = $this->paymentTotals($tenantId, $organizationUnitId);

        $query = DB::query()
            ->fromSub($commissionRows, 'commission_rows')
            ->leftJoinSub($invoiceTotals, 'invoice_totals', 'invoice_totals.vehicle_service_job_id', '=', 'commission_rows.job_id')
            ->leftJoinSub($paymentTotals, 'payment_totals', 'payment_totals.vehicle_service_job_id', '=', 'commission_rows.job_id')
            ->select([
                'commission_rows.*',
                DB::raw('COALESCE(invoice_totals.invoice_count, 0) as invoice_count'),
                DB::raw('COALESCE(invoice_totals.draft_invoice_count, 0) as draft_invoice_count'),
                DB::raw('COALESCE(invoice_totals.posted_invoice_count, 0) as posted_invoice_count'),
                DB::raw('COALESCE(invoice_totals.invoice_total, 0) as invoice_total'),
                DB::raw('COALESCE(payment_totals.paid_total, 0) as paid_total'),
            ]);

        $this->applyFilters($query, $params, $tenantId, $organizationUnitId);

        return $query;
    }

    private function invoiceTotals(int $tenantId, mixed $organizationUnitId): Builder
    {
        $query = DB::table('vehicle_service_invoice_links as links')
            ->join('invoices', 'invoices.id', '=', 'links.invoice_id')
            ->where('links.tenant_id', $tenantId)
            ->where('links.status', 'active')
            ->whereNull('invoices.deleted_at')
            ->whereNotIn('invoices.status', ['cancelled', 'void'])
            ->selectRaw(
                'links.vehicle_service_job_id, COUNT(DISTINCT invoices.id) as invoice_count, '
                ."SUM(CASE WHEN invoices.status IN ('draft', 'approved') THEN 1 ELSE 0 END) as draft_invoice_count, "
                ."SUM(CASE WHEN invoices.status IN ('posted', 'partially_paid', 'paid') THEN 1 ELSE 0 END) as posted_invoice_count, "
                .'COALESCE(SUM(links.invoice_total), 0) as invoice_total'
            )
            ->groupBy('links.vehicle_service_job_id');
        $this->organizationScope($query, 'links.organization_unit_id', $organizationUnitId);

        return $query;
    }


    private function paymentTotals(int $tenantId, mixed $organizationUnitId): Builder
    {
        $query = DB::table('vehicle_service_payment_links as links')
            ->join('payments', 'payments.id', '=', 'links.payment_id')
            ->where('links.tenant_id', $tenantId)
            ->where('links.status', 'active')
            ->whereNull('payments.deleted_at')
            ->whereIn('payments.status', ['posted', 'partially_allocated', 'fully_allocated', 'allocated'])
            ->selectRaw(
                'links.vehicle_service_job_id, COALESCE(SUM(links.allocated_amount), 0) as paid_total'
            )
            ->groupBy('links.vehicle_service_job_id');
        $this->organizationScope($query, 'links.organization_unit_id', $organizationUnitId);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function applyFilters(Builder $query, array $params, int $tenantId, mixed $organizationUnitId): void
    {
        if (! empty($params['date_from'])) {
            $query->whereDate('commission_rows.job_date', '>=', (string) $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('commission_rows.job_date', '<=', (string) $params['date_to']);
        }

        $this->whereInteger($query, 'commission_rows.employee_id', $params['employee_id'] ?? null);
        $this->whereInteger($query, 'commission_rows.department_id', $params['department_id'] ?? null);
        $this->whereInteger($query, 'commission_rows.designation_id', $params['designation_id'] ?? null);
        $this->whereInteger($query, 'commission_rows.job_supervisor_id', $params['supervisor_id'] ?? null);
        $this->whereInteger($query, 'commission_rows.customer_id', $params['customer_id'] ?? null);
        $this->whereInteger($query, 'commission_rows.vehicle_id', $params['vehicle_id'] ?? null);
        $this->whereString($query, 'commission_rows.job_status', $params['job_status'] ?? null);
        $this->whereString($query, 'commission_rows.commission_type', $params['commission_type'] ?? null);
        $this->whereString($query, 'commission_rows.commission_source', $params['commission_source'] ?? null);
        $this->whereString($query, 'commission_rows.role_type', $params['role_type'] ?? null);
        $this->whereString($query, 'commission_rows.commission_status', $params['commission_status'] ?? null);

        if (! empty($params['invoice_status'])) {
            $status = (string) $params['invoice_status'];
            $query->whereExists(function (Builder $invoice) use ($tenantId, $organizationUnitId, $status): void {
                $invoice->selectRaw('1')
                    ->from('vehicle_service_invoice_links as filter_links')
                    ->join('invoices as filter_invoices', 'filter_invoices.id', '=', 'filter_links.invoice_id')
                    ->whereColumn('filter_links.vehicle_service_job_id', 'commission_rows.job_id')
                    ->where('filter_links.tenant_id', $tenantId)
                    ->where('filter_links.status', 'active')
                    ->whereNull('filter_invoices.deleted_at')
                    ->where('filter_invoices.status', $status);
                $this->organizationScope($invoice, 'filter_links.organization_unit_id', $organizationUnitId);
            });
        }

        if (! empty($params['payment_status'])) {
            $status = (string) $params['payment_status'];
            $query->whereExists(function (Builder $payment) use ($tenantId, $organizationUnitId, $status): void {
                $payment->selectRaw('1')
                    ->from('vehicle_service_payment_links as filter_links')
                    ->join('payments as filter_payments', 'filter_payments.id', '=', 'filter_links.payment_id')
                    ->whereColumn('filter_links.vehicle_service_job_id', 'commission_rows.job_id')
                    ->where('filter_links.tenant_id', $tenantId)
                    ->where('filter_links.status', 'active')
                    ->whereNull('filter_payments.deleted_at')
                    ->where('filter_payments.status', $status);
                $this->organizationScope($payment, 'filter_links.organization_unit_id', $organizationUnitId);
            });
        }

        $search = $this->searchTerm($params['search'] ?? null);
        if ($search !== null) {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('commission_rows.employee_code', 'like', $search)
                    ->orWhere('commission_rows.employee_name', 'like', $search)
                    ->orWhere('commission_rows.department_name', 'like', $search)
                    ->orWhere('commission_rows.designation_name', 'like', $search)
                    ->orWhere('commission_rows.job_supervisor_name', 'like', $search)
                    ->orWhere('commission_rows.job_number', 'like', $search)
                    ->orWhere('commission_rows.customer_name', 'like', $search)
                    ->orWhere('commission_rows.vehicle_label', 'like', $search)
                    ->orWhere('commission_rows.work_description', 'like', $search)
                    ->orWhere('commission_rows.role_type', 'like', $search)
                    ->orWhere('commission_rows.commission_source', 'like', $search);
            });
        }
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function sort(Builder $query, array $params): void
    {
        $columns = [
            'employee_code' => 'commission_rows.employee_code',
            'employee_name' => 'commission_rows.employee_name',
            'employee' => 'commission_rows.employee_name',
            'department' => 'commission_rows.department_name',
            'designation' => 'commission_rows.designation_name',
            'supervisor' => 'commission_rows.job_supervisor_name',
            'commission_source' => 'commission_rows.commission_source',
            'role_type' => 'commission_rows.role_type',
            'job_number' => 'commission_rows.job_number',
            'job_date' => 'commission_rows.job_date',
            'customer' => 'commission_rows.customer_name',
            'vehicle' => 'commission_rows.vehicle_label',
            'assigned_hours' => 'commission_rows.assigned_hours',
            'rate' => 'commission_rows.rate',
            'labour_amount' => 'commission_rows.labour_amount',
            'commission_base' => 'commission_rows.commission_base',
            'commission_type' => 'commission_rows.commission_type',
            'commission_value' => 'commission_rows.commission_value',
            'commission_amount' => 'commission_rows.commission_amount',
            'commission_status' => 'commission_rows.commission_status',
            'job_status' => 'commission_rows.job_status',
        ];
        $groupColumns = [
            'employee' => 'commission_rows.employee_name',
            'department' => 'commission_rows.department_name',
            'designation' => 'commission_rows.designation_name',
            'supervisor' => 'commission_rows.job_supervisor_name',
            'commission_source' => 'commission_rows.commission_source',
        ];
        $sort = (string) ($params['sort'] ?? 'job_date');
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $groupBy = (string) ($params['group_by'] ?? 'employee');

        $hasExplicitSort = array_key_exists('sort', $params) && trim((string) $params['sort']) !== '';
        if (! $hasExplicitSort && isset($groupColumns[$groupBy])) {
            $query->orderBy($groupColumns[$groupBy]);
        }

        $query->orderBy($columns[$sort] ?? $columns['job_date'], $direction);
        if ($hasExplicitSort && isset($groupColumns[$groupBy])) {
            $query->orderBy($groupColumns[$groupBy]);
        }

        $query
            ->orderBy('commission_rows.job_id', 'desc')
            ->orderBy('commission_rows.commission_source')
            ->orderBy('commission_rows.source_id');
    }

    /**
     * @return array<string, mixed>
     */
    private function metrics(Builder $query, string $groupBy): array
    {
        $totals = (clone $query)->select([])->selectRaw(
            'COUNT(*) as total_entries, COUNT(DISTINCT commission_rows.employee_id) as total_employees, '
            .'COUNT(DISTINCT commission_rows.job_id) as total_jobs, '
            ."COALESCE(SUM(CASE WHEN commission_rows.commission_status <> 'cancelled' THEN commission_rows.assigned_hours ELSE 0 END), 0) as total_hours, "
            ."COALESCE(SUM(CASE WHEN commission_rows.commission_status <> 'cancelled' THEN commission_rows.labour_amount ELSE 0 END), 0) as total_labour_value, "
            ."COALESCE(SUM(CASE WHEN commission_rows.commission_status <> 'cancelled' THEN commission_rows.commission_base ELSE 0 END), 0) as total_commission_base, "
            ."COALESCE(SUM(CASE WHEN commission_rows.commission_source = 'technician' AND commission_rows.commission_status <> 'cancelled' THEN commission_rows.commission_amount ELSE 0 END), 0) as technician_commission, "
            ."COALESCE(SUM(CASE WHEN commission_rows.commission_source = 'supervisor' AND commission_rows.commission_status <> 'cancelled' THEN commission_rows.commission_amount ELSE 0 END), 0) as supervisor_commission, "
            ."COALESCE(SUM(CASE WHEN commission_rows.commission_status = 'earned' THEN commission_rows.commission_amount ELSE 0 END), 0) as earned_commission, "
            ."COALESCE(SUM(CASE WHEN commission_rows.commission_status = 'pending' THEN commission_rows.commission_amount ELSE 0 END), 0) as pending_commission, "
            ."COALESCE(SUM(CASE WHEN commission_rows.commission_status = 'cancelled' THEN commission_rows.commission_amount ELSE 0 END), 0) as cancelled_commission, "
            ."COALESCE(SUM(CASE WHEN commission_rows.commission_status <> 'cancelled' THEN commission_rows.commission_amount ELSE 0 END), 0) as total_commission"
        )->first();

        $totalCommission = $this->decimal($totals->total_commission ?? 0);
        $totalHours = $this->decimal($totals->total_hours ?? 0);
        $totalJobs = (int) ($totals->total_jobs ?? 0);
        $totalEmployees = (int) ($totals->total_employees ?? 0);

        return [
            'summary' => [
                'total_entries' => (int) ($totals->total_entries ?? 0),
                'total_employees' => $totalEmployees,
                'total_jobs' => $totalJobs,
                'total_hours' => $totalHours,
                'total_labour_value' => $this->decimal($totals->total_labour_value ?? 0),
                'total_commission_base' => $this->decimal($totals->total_commission_base ?? 0),
                'technician_commission' => $this->decimal($totals->technician_commission ?? 0),
                'supervisor_commission' => $this->decimal($totals->supervisor_commission ?? 0),
                'earned_commission' => $this->decimal($totals->earned_commission ?? 0),
                'pending_commission' => $this->decimal($totals->pending_commission ?? 0),
                'cancelled_commission' => $this->decimal($totals->cancelled_commission ?? 0),
                'total_commission' => $totalCommission,
                'average_commission_per_job' => $totalJobs > 0
                    ? $this->math->div($totalCommission, (string) $totalJobs)
                    : $this->decimal(0),
                'average_commission_per_employee' => $totalEmployees > 0
                    ? $this->math->div($totalCommission, (string) $totalEmployees)
                    : $this->decimal(0),
                'average_commission_per_hour' => $this->math->compare($totalHours, '0') > 0
                    ? $this->math->div($totalCommission, $totalHours)
                    : $this->decimal(0),
            ],
            'rankings' => [
                'top_earning_employee' => $this->topEmployee(clone $query, 'labour_amount'),
                'top_commission_employee' => $this->topEmployee(clone $query, 'commission_amount'),
            ],
            'groups' => $this->groups(clone $query, $groupBy),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function topEmployee(Builder $query, string $metric): ?array
    {
        $row = $query
            ->where('commission_rows.commission_status', '<>', 'cancelled')
            ->select([])
            ->selectRaw(
                'commission_rows.employee_id, MAX(commission_rows.employee_code) as employee_code, '
                .'MAX(commission_rows.employee_name) as employee_name, '
                .'MAX(commission_rows.department_id) as department_id, MAX(commission_rows.department_code) as department_code, '
                .'MAX(commission_rows.department_name) as department_name, '
                .'MAX(commission_rows.designation_id) as designation_id, MAX(commission_rows.designation_code) as designation_code, '
                .'MAX(commission_rows.designation_name) as designation_name, '
                .'COALESCE(SUM(commission_rows.labour_amount), 0) as labour_value, '
                .'COALESCE(SUM(commission_rows.commission_amount), 0) as commission_amount'
            )
            ->groupBy('commission_rows.employee_id')
            ->orderByDesc($metric === 'labour_amount' ? 'labour_value' : 'commission_amount')
            ->orderBy('employee_name')
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'employee' => $this->employeeResource($row),
            'labour_value' => $this->decimal($row->labour_value),
            'commission_amount' => $this->decimal($row->commission_amount),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groups(Builder $query, string $groupBy): array
    {
        [$key, $label, $resourceType] = match ($groupBy) {
            'department' => ['commission_rows.department_id', 'commission_rows.department_name', 'department'],
            'designation' => ['commission_rows.designation_id', 'commission_rows.designation_name', 'designation'],
            'supervisor' => ['commission_rows.job_supervisor_id', 'commission_rows.job_supervisor_name', 'supervisor'],
            'commission_source' => ['commission_rows.commission_source', 'commission_rows.commission_source', 'source'],
            default => ['commission_rows.employee_id', 'commission_rows.employee_name', 'employee'],
        };

        return $query
            ->select([])
            ->selectRaw(
                "{$key} as group_key, MAX({$label}) as group_label, "
                .'COUNT(DISTINCT commission_rows.job_id) as total_jobs, '
                ."COALESCE(SUM(CASE WHEN commission_rows.commission_status <> 'cancelled' THEN commission_rows.assigned_hours ELSE 0 END), 0) as total_hours, "
                ."COALESCE(SUM(CASE WHEN commission_rows.commission_status <> 'cancelled' THEN commission_rows.labour_amount ELSE 0 END), 0) as total_labour_value, "
                ."COALESCE(SUM(CASE WHEN commission_rows.commission_status <> 'cancelled' THEN commission_rows.commission_amount ELSE 0 END), 0) as total_commission"
            )
            ->groupBy($key)
            ->orderBy('group_label')
            ->get()
            ->map(function (object $row) use ($resourceType): array {
                $key = $row->group_key === null || $row->group_key === '' ? 'unassigned' : (string) $row->group_key;
                $label = trim((string) ($row->group_label ?? '')) ?: 'Unassigned';

                return [
                    'key' => $key,
                    'label' => $label,
                    'resource' => $resourceType === 'source' || $key === 'unassigned'
                        ? null
                        : ['id' => (int) $row->group_key, 'code' => '', 'name' => $label],
                    'total_jobs' => (int) $row->total_jobs,
                    'total_hours' => $this->decimal($row->total_hours),
                    'total_labour_value' => $this->decimal($row->total_labour_value),
                    'total_commission' => $this->decimal($row->total_commission),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(object $row, string $groupBy): array
    {
        $group = $this->groupResource($row, $groupBy);

        return [
            'id' => (string) $row->commission_source.'-'.(string) $row->source_id,
            'commission_source' => (string) $row->commission_source,
            'employee' => $this->employeeResource($row),
            'employee_code' => (string) ($row->employee_code ?? ''),
            'employee_name' => (string) ($row->employee_name ?? ''),
            'department' => $this->namedResource($row->department_id ?? null, $row->department_code ?? null, $row->department_name ?? null),
            'department_name' => (string) ($row->department_name ?? ''),
            'designation' => $this->namedResource($row->designation_id ?? null, $row->designation_code ?? null, $row->designation_name ?? null),
            'designation_name' => (string) ($row->designation_name ?? ''),
            'job' => ['id' => (int) $row->job_id, 'code' => (string) $row->job_number, 'name' => (string) $row->job_number],
            'job_number' => (string) $row->job_number,
            'job_date' => (string) $row->job_date,
            'job_status' => (string) $row->job_status,
            'customer' => $this->namedResource($row->customer_id ?? null, $row->customer_code ?? null, $row->customer_name ?? null),
            'customer_name' => (string) ($row->customer_name ?? ''),
            'vehicle' => $this->namedResource($row->vehicle_id ?? null, $row->vehicle_code ?? null, $row->vehicle_label ?? null),
            'vehicle_label' => (string) ($row->vehicle_label ?? ''),
            'supervisor' => $this->namedResource($row->job_supervisor_id ?? null, $row->job_supervisor_code ?? null, $row->job_supervisor_name ?? null),
            'supervisor_name' => (string) ($row->job_supervisor_name ?? ''),
            'line_description' => (string) ($row->work_description ?? ''),
            'role_type' => (string) $row->role_type,
            'assigned_hours' => $this->decimal($row->assigned_hours),
            'rate' => $this->decimal($row->rate),
            'labour_amount' => $this->decimal($row->labour_amount),
            'commission_base' => $this->decimal($row->commission_base),
            'commission_type' => (string) $row->commission_type,
            'commission_value' => $this->decimal($row->commission_value),
            'commission_amount' => $this->decimal($row->commission_amount),
            'commission_status' => (string) $row->commission_status,
            'completed_at' => $row->completed_at === null ? null : (string) $row->completed_at,
            'invoice_progress' => $this->invoiceProgress($row),
            'payment_progress' => $this->paymentProgress($row),
            'invoice_total' => $this->decimal($row->invoice_total),
            'paid_total' => $this->decimal($row->paid_total),
            'balance_due' => $this->balanceDue($row),
            'group_key' => $group['key'],
            'group_label' => $group['label'],
        ];
    }

    /**
     * @return array{key: string, label: string}
     */
    private function groupResource(object $row, string $groupBy): array
    {
        [$key, $label] = match ($groupBy) {
            'department' => [$row->department_id ?? null, $row->department_name ?? null],
            'designation' => [$row->designation_id ?? null, $row->designation_name ?? null],
            'supervisor' => [$row->job_supervisor_id ?? null, $row->job_supervisor_name ?? null],
            'commission_source' => [$row->commission_source ?? null, $row->commission_source ?? null],
            default => [$row->employee_id ?? null, $row->employee_name ?? null],
        };

        return [
            'key' => $key === null || $key === '' ? 'unassigned' : (string) $key,
            'label' => trim((string) $label) ?: 'Unassigned',
        ];
    }

    private function invoiceProgress(object $row): string
    {
        if ((int) $row->invoice_count === 0) {
            return 'uninvoiced';
        }
        if ((int) $row->draft_invoice_count > 0) {
            return 'draft';
        }
        if (in_array((string) $row->job_status, ['invoiced', 'partially_paid', 'paid'], true)) {
            return 'invoiced';
        }

        return (int) $row->posted_invoice_count > 0 ? 'partially_invoiced' : 'uninvoiced';
    }

    private function paymentProgress(object $row): string
    {
        if ((int) $row->invoice_count === 0) {
            return 'not_applicable';
        }
        if ($this->math->compare((string) $row->invoice_total, '0') > 0
            && $this->math->compare($this->balanceDue($row), '0') <= 0) {
            return 'paid';
        }
        if ($this->math->compare((string) $row->paid_total, '0') > 0) {
            return 'partially_paid';
        }

        return 'unpaid';
    }


    private function balanceDue(object $row): string
    {
        $balance = $this->math->sub(
            $this->decimal($row->invoice_total),
            $this->decimal($row->paid_total),
        );

        return $this->math->isNegative($balance) ? $this->decimal(0) : $balance;
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeResource(object $row): array
    {
        return [
            'id' => (int) $row->employee_id,
            'code' => (string) ($row->employee_code ?? ''),
            'name' => (string) ($row->employee_name ?? ''),
            'department' => $this->namedResource($row->department_id ?? null, $row->department_code ?? null, $row->department_name ?? null),
            'designation' => $this->namedResource($row->designation_id ?? null, $row->designation_code ?? null, $row->designation_name ?? null),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function namedResource(mixed $id, mixed $code, mixed $name): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        return [
            'id' => (int) $id,
            'code' => (string) ($code ?? ''),
            'name' => (string) ($name ?? ''),
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

        return '%'.str_replace(['%', '_'], ['\\%', '\\_'], $value).'%';
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? '0'));
    }
}
