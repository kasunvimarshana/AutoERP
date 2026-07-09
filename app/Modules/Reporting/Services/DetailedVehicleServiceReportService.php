<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Reporting\DTOs\ReportColumn;
use Modules\Reporting\DTOs\ReportDefinition;
use Modules\VehicleService\Models\VehicleServiceJobLine;

final class DetailedVehicleServiceReportService
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
            key: 'vehicle-service/detailed',
            title: 'Detailed Vehicle Service Report',
            group: 'Vehicle Service',
            model: VehicleServiceJobLine::class,
            description: 'Line-level service-job revenue, recorded direct cost, employee incentives and invoice/payment progress.',
            columns: [
                new ReportColumn('job_number', 'Job number', sortBy: 'job_number'),
                new ReportColumn('job_date', 'Job date', sortBy: 'job_date', format: 'date'),
                new ReportColumn('operational_status', 'Operational status', sortBy: 'operational_status'),
                new ReportColumn('billing_status', 'Billing status', sortBy: 'billing_status'),
                new ReportColumn('payment_status', 'Payment status', sortBy: 'payment_status'),
                new ReportColumn('customer', 'Customer', sortBy: 'customer'),
                new ReportColumn('vehicle', 'Vehicle', sortBy: 'vehicle'),
                new ReportColumn('supervisor', 'Supervisor'),
                new ReportColumn('line_number', 'Line', sortBy: 'line_number', format: 'decimal'),
                new ReportColumn('line_source_type', 'Source', sortBy: 'line_source_type'),
                new ReportColumn('description', 'Work / item'),
                new ReportColumn('item_code', 'Item code'),
                new ReportColumn('uom', 'UOM'),
                new ReportColumn('quantity', 'Quantity', sortBy: 'quantity', format: 'decimal', summarize: true),
                new ReportColumn('unit_cost', 'Unit cost', sortBy: 'unit_cost', format: 'money'),
                new ReportColumn('recorded_direct_cost', 'Recorded direct cost', sortBy: 'recorded_direct_cost', format: 'money', summarize: true),
                new ReportColumn('unit_price', 'Unit price', sortBy: 'unit_price', format: 'money'),
                new ReportColumn('discount_amount', 'Discount', format: 'money', summarize: true),
                new ReportColumn('tax_amount', 'Tax', format: 'money', summarize: true),
                new ReportColumn('charge_amount', 'Charge', format: 'money', summarize: true),
                new ReportColumn('line_total', 'Revenue', sortBy: 'line_total', format: 'money', summarize: true),
                new ReportColumn('assigned_employee_count', 'Employees', format: 'decimal'),
                new ReportColumn('employee_incentive', 'Employee incentive', sortBy: 'employee_incentive', format: 'money', summarize: true),
                new ReportColumn('estimated_contribution', 'Estimated contribution', sortBy: 'estimated_contribution', format: 'money', summarize: true),
                new ReportColumn('invoice_total', 'Invoiced total', format: 'money'),
                new ReportColumn('paid_total', 'Paid total', format: 'money'),
                new ReportColumn('balance_due', 'Balance due', format: 'money'),
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

        $employeeTotals = DB::table('vehicle_service_line_employees')
            ->where('tenant_id', $tenantId)
            ->selectRaw(
                'vehicle_service_job_line_id, COUNT(*) as assigned_employee_count, '
                .'COALESCE(SUM(commission_amount), 0) as employee_incentive'
            )
            ->groupBy('vehicle_service_job_line_id');
        $this->organizationScope($employeeTotals, 'organization_unit_id', $organizationUnitId);

        $invoiceTotals = DB::table('vehicle_service_invoice_links as links')
            ->join('invoices', 'invoices.id', '=', 'links.invoice_id')
            ->leftJoin('invoice_balances as balances', 'balances.invoice_id', '=', 'invoices.id')
            ->where('links.tenant_id', $tenantId)
            ->where('links.status', 'active')
            ->whereNull('invoices.deleted_at')
            ->selectRaw(
                'links.vehicle_service_job_id, COUNT(DISTINCT invoices.id) as invoice_count, '
                .'COALESCE(SUM(links.invoice_total), 0) as invoice_total, '
                .'COALESCE(SUM(balances.paid_amount), 0) as paid_total, '
                .'COALESCE(SUM(balances.remaining_amount), 0) as balance_due'
            )
            ->groupBy('links.vehicle_service_job_id');
        $this->organizationScope($invoiceTotals, 'links.organization_unit_id', $organizationUnitId);

        $query = DB::table('vehicle_service_job_lines as lines')
            ->join('vehicle_service_jobs as jobs', 'jobs.id', '=', 'lines.vehicle_service_job_id')
            ->leftJoin('customers', 'customers.id', '=', 'jobs.customer_id')
            ->leftJoin('vehicles', 'vehicles.id', '=', 'jobs.vehicle_id')
            ->leftJoin('hr_employees as supervisors', 'supervisors.id', '=', 'jobs.supervisor_employee_id')
            ->leftJoin('items', 'items.id', '=', 'lines.item_id')
            ->leftJoin('unit_of_measures as uoms', 'uoms.id', '=', 'lines.uom_id')
            ->leftJoinSub($employeeTotals, 'employee_totals', 'employee_totals.vehicle_service_job_line_id', '=', 'lines.id')
            ->leftJoinSub($invoiceTotals, 'invoice_totals', 'invoice_totals.vehicle_service_job_id', '=', 'jobs.id')
            ->whereNull('jobs.deleted_at')
            ->where('jobs.tenant_id', $tenantId)
            ->where('lines.tenant_id', $tenantId)
            ->select([
                'lines.id',
                'jobs.id as job_id',
                'jobs.job_number',
                'jobs.job_date',
                'jobs.operational_status',
                'jobs.billing_status',
                'jobs.payment_status',
                'jobs.supervisor_commission_amount',
                'customers.code as customer_code',
                'customers.name as customer_name',
                'vehicles.vehicle_number',
                'vehicles.registration_number',
                'supervisors.employee_number as supervisor_code',
                'supervisors.display_name as supervisor_name',
                'lines.line_number',
                'lines.line_source_type',
                'lines.description',
                'lines.item_id',
                'items.code as item_code',
                'items.name as item_name',
                'uoms.code as uom_code',
                'lines.quantity',
                'lines.unit_cost',
                'lines.unit_price',
                'lines.discount_amount',
                'lines.tax_amount',
                'lines.charge_amount',
                'lines.line_total',
                DB::raw('COALESCE(employee_totals.assigned_employee_count, 0) as assigned_employee_count'),
                DB::raw('COALESCE(employee_totals.employee_incentive, 0) as employee_incentive'),
                DB::raw('COALESCE(invoice_totals.invoice_count, 0) as invoice_count'),
                DB::raw('COALESCE(invoice_totals.invoice_total, 0) as invoice_total'),
                DB::raw('COALESCE(invoice_totals.paid_total, 0) as paid_total'),
                DB::raw('COALESCE(invoice_totals.balance_due, 0) as balance_due'),
            ]);

        $this->organizationScope($query, 'jobs.organization_unit_id', $organizationUnitId);
        $this->organizationScope($query, 'lines.organization_unit_id', $organizationUnitId);

        if (! empty($params['date_from'])) {
            $query->whereDate('jobs.job_date', '>=', (string) $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('jobs.job_date', '<=', (string) $params['date_to']);
        }
        if (! empty($params['operational_status'])) {
            $query->where('jobs.operational_status', (string) $params['operational_status']);
        }
        if (! empty($params['billing_status'])) {
            $query->where('jobs.billing_status', (string) $params['billing_status']);
        }
        if (! empty($params['payment_status'])) {
            $query->where('jobs.payment_status', (string) $params['payment_status']);
        }
        if (! empty($params['line_source_type'])) {
            $query->where('lines.line_source_type', (string) $params['line_source_type']);
        }
        if (! empty($params['customer_id'])) {
            $query->where('jobs.customer_id', (int) $params['customer_id']);
        }
        if (! empty($params['vehicle_id'])) {
            $query->where('jobs.vehicle_id', (int) $params['vehicle_id']);
        }
        if (! empty($params['item_id'])) {
            $query->where('lines.item_id', (int) $params['item_id']);
        }
        if (! empty($params['employee_id'])) {
            $employeeId = (int) $params['employee_id'];
            $query->whereExists(function (Builder $assignment) use ($employeeId): void {
                $assignment
                    ->selectRaw('1')
                    ->from('vehicle_service_line_employees as assignments')
                    ->whereColumn('assignments.vehicle_service_job_line_id', 'lines.id')
                    ->where('assignments.employee_id', $employeeId);
            });
        }

        $search = $this->searchTerm($params['search'] ?? null);
        if ($search !== null) {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('jobs.job_number', 'like', $search)
                    ->orWhere('jobs.operational_status', 'like', $search)
                    ->orWhere('jobs.billing_status', 'like', $search)
                    ->orWhere('jobs.payment_status', 'like', $search)
                    ->orWhere('customers.code', 'like', $search)
                    ->orWhere('customers.name', 'like', $search)
                    ->orWhere('vehicles.vehicle_number', 'like', $search)
                    ->orWhere('vehicles.registration_number', 'like', $search)
                    ->orWhere('items.code', 'like', $search)
                    ->orWhere('items.name', 'like', $search)
                    ->orWhere('lines.description', 'like', $search);
            });
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Builder $query): array
    {
        $totals = (clone $query)->select([])->selectRaw(
            'COUNT(DISTINCT jobs.id) as total_jobs, COUNT(lines.id) as total_lines, '
            .'COALESCE(SUM(lines.quantity), 0) as total_quantity, '
            .'COALESCE(SUM(lines.quantity * lines.unit_cost), 0) as recorded_direct_cost, '
            .'COALESCE(SUM(lines.line_total), 0) as revenue, '
            .'COALESCE(SUM(COALESCE(employee_totals.employee_incentive, 0)), 0) as employee_incentive'
        )->first();

        $jobIds = (clone $query)->select('jobs.id')->distinct();
        $supervisorIncentive = DB::query()
            ->fromSub($jobIds, 'filtered_jobs')
            ->join('vehicle_service_jobs as summary_jobs', 'summary_jobs.id', '=', 'filtered_jobs.id')
            ->sum('summary_jobs.supervisor_commission_amount');

        $revenue = $this->decimal($totals->revenue ?? 0);
        $directCost = $this->decimal($totals->recorded_direct_cost ?? 0);
        $employeeIncentive = $this->decimal($totals->employee_incentive ?? 0);
        $supervisor = $this->decimal($supervisorIncentive);
        $estimatedContribution = $this->math->sub(
            $this->math->sub($this->math->sub($revenue, $directCost), $employeeIncentive),
            $supervisor,
        );

        return [
            'total_jobs' => (int) ($totals->total_jobs ?? 0),
            'total_lines' => (int) ($totals->total_lines ?? 0),
            'total_quantity' => $this->decimal($totals->total_quantity ?? 0),
            'revenue' => $revenue,
            'recorded_direct_cost' => $directCost,
            'employee_incentive' => $employeeIncentive,
            'supervisor_incentive' => $supervisor,
            'estimated_contribution' => $estimatedContribution,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function sort(Builder $query, array $params): void
    {
        $columns = [
            'job_number' => 'jobs.job_number',
            'job_date' => 'jobs.job_date',
            'operational_status' => 'jobs.operational_status',
            'billing_status' => 'jobs.billing_status',
            'payment_status' => 'jobs.payment_status',
            'customer' => 'customers.name',
            'vehicle' => 'vehicles.registration_number',
            'line_number' => 'lines.line_number',
            'line_source_type' => 'lines.line_source_type',
            'quantity' => 'lines.quantity',
            'unit_cost' => 'lines.unit_cost',
            'recorded_direct_cost' => DB::raw('lines.quantity * lines.unit_cost'),
            'unit_price' => 'lines.unit_price',
            'line_total' => 'lines.line_total',
            'employee_incentive' => 'employee_totals.employee_incentive',
            'estimated_contribution' => DB::raw('(lines.line_total - (lines.quantity * lines.unit_cost) - COALESCE(employee_totals.employee_incentive, 0))'),
        ];
        $sort = (string) ($params['sort'] ?? 'job_date');
        $direction = (string) ($params['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query
            ->orderBy($columns[$sort] ?? $columns['job_date'], $direction)
            ->orderBy('jobs.id', 'desc')
            ->orderBy('lines.line_number');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(object $row): array
    {
        $directCost = $this->math->mul((string) $row->quantity, (string) $row->unit_cost);
        $employeeIncentive = $this->decimal($row->employee_incentive);
        $estimatedContribution = $this->math->sub(
            $this->math->sub((string) $row->line_total, $directCost),
            $employeeIncentive,
        );

        return [
            'id' => (int) $row->id,
            'job_number' => (string) $row->job_number,
            'job_date' => (string) $row->job_date,
            'operational_status' => (string) $row->operational_status,
            'billing_status' => (string) $row->billing_status,
            'payment_status' => (string) $row->payment_status,
            'customer' => trim((string) ($row->customer_code ?? '').' '.(string) ($row->customer_name ?? '')),
            'vehicle' => trim((string) ($row->vehicle_number ?? '').' '.(string) ($row->registration_number ?? '')),
            'supervisor' => trim((string) ($row->supervisor_code ?? '').' '.(string) ($row->supervisor_name ?? '')),
            'line_number' => (int) $row->line_number,
            'line_source_type' => (string) $row->line_source_type,
            'description' => (string) $row->description,
            'item_code' => (string) ($row->item_code ?? ''),
            'item_name' => (string) ($row->item_name ?? ''),
            'uom' => (string) ($row->uom_code ?? ''),
            'quantity' => $this->decimal($row->quantity),
            'unit_cost' => $this->decimal($row->unit_cost),
            'recorded_direct_cost' => $directCost,
            'unit_price' => $this->decimal($row->unit_price),
            'discount_amount' => $this->decimal($row->discount_amount),
            'tax_amount' => $this->decimal($row->tax_amount),
            'charge_amount' => $this->decimal($row->charge_amount),
            'line_total' => $this->decimal($row->line_total),
            'assigned_employee_count' => (int) $row->assigned_employee_count,
            'employee_incentive' => $employeeIncentive,
            'estimated_contribution' => $estimatedContribution,
            'job_supervisor_incentive' => $this->decimal($row->supervisor_commission_amount),
            'invoice_total' => $this->decimal($row->invoice_total),
            'paid_total' => $this->decimal($row->paid_total),
            'balance_due' => $this->decimal($row->balance_due),
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
